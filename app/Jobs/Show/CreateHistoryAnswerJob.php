<?php

namespace App\Jobs\Show;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;


use App\Services\GptService;
use App\Services\DalleService;
use App\Services\ElevenlabsService;
use App\Services\VideoSubtitleCreator;
use App\Services\FfmpegService;
use App\Services\PanAndZoom;
use App\Services\StressService;
use App\Services\TextSplitter;

use App\Models\Show;
use App\Models\Episode;

class CreateHistoryAnswerJob implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

 	public $timeout = 3600;

	private float $microtime = 0;
	
	private Show $show;

	/**
	 * Create a new job instance.
	 */
	public function __construct(Show $show)
	{
		$this->show = $show;
	}

	/**
	 * Execute the job.
	 */
	public function handle(): void
	{
		$this->microtime = microtime(true);

		print 'Creating show «' . $this->show->name . '»' . PHP_EOL;


/*
		$showText = "В этом видео мы открываем мрачные страницы истории СССР, лишённые красивого советского облика. Речь пойдёт о страшных и засекреченных тайнах прошлого: страшная ядерная авария под Кыштымом в 1957 году, гибель космонавта Валентина Бондаренко, неизвестная науке болезнь Голодомор, восстание 1932-1933 годов, трагедия в Катынском лесу, а также о закрытых городах, которые никогда не отображались на картах. Как вы считаете, что стоило бы предпринять современному обществу для того, чтобы исключить повторение подобных страшных событий в будущем? Записывайте свои мысли в комментариях!";
		dump($showText);
		$textSplitter = new TextSplitter;
		$groups = $textSplitter->splitTextIntoGroups($showText, 5);
		dump($groups);

		exit;
		*/



        $tempDir = storage_path('app/show_' . $this->show->id);
        if (is_dir($tempDir) == false) {
            mkdir($tempDir);
            print '✓ TempDir created' . PHP_EOL;
        }


		$episodesCount = $this->show->episodes()->count();

		if ($episodesCount == 0) {
			print '### Creating episodes...' . PHP_EOL;

			print '#############################' . PHP_EOL;
			print '### Creating Propmpt for GPT.' . PHP_EOL;

			$prompt = 'Я создаю видео-слайдшоу по теме «' . $this->show->name . '»'  . PHP_EOL;
			//$prompt.= 'Пожалуйста изучи статью приведенную ниже и на ее основе подготовь текст с раскрытием темы, общим объемом 100-120 слов.'  . PHP_EOL;


			$prompt.= 'Пожалуйста подготовь текст с раскрытием темы, общим объемом 100-120 слов.'  . PHP_EOL;
			
			$r = rand(0,3);

			if ($r == 0) {
				$prompt.= 'В последнем предложении - предложи пользователям подписаться на канал, чтобы узнавать новые интересный факты из истории Древней Руси, Российской Империи, СССР и России.'  . PHP_EOL;
			} else {
				$prompt.= 'В последнем предложении - предложи вопрос-интерактив для зрителей, чтобы они что-то написали в комментариях к видео-слайдшоу, например: «А как вы считаете, современные ученые смогут запустить спутник к юпитеру? Пишите ответы в комментариях!». Вопрос должен быть плотно связан с темой видео и побуждать зрителей написать комментарий!'  . PHP_EOL;
			}

			$prompt.= 'Если это уместно, приведи конкретные исторические факты.'  . PHP_EOL;
			$prompt.= 'Исключи общие утверждения, которые не дают смысловой нагрузки.'  . PHP_EOL;
			$prompt.= 'Если в тексте отсылка к историческим личностям - УКАЗЫВАЙ ПОЛНОЕ ИМЯ И ФАМИЛИЮ, вместо инициалов.'  . PHP_EOL;
			$prompt.= 'Не используй сокращения, типа г. (год), итд. (и так далее), др (другие). Пишите полные слова.'  . PHP_EOL;
			$prompt.= 'Используй букву Ё в словах там где она нужна.'  . PHP_EOL;
			$prompt.= 'В ответе верни только текст для видео-слайдшоу.'  . PHP_EOL . PHP_EOL . PHP_EOL;
			//$prompt.= 'Текст статьи:'  . PHP_EOL;
			//$prompt.= $this->show->content;
			
			print '- FIRST propmpt for GPT:' . PHP_EOL;
			print $prompt . PHP_EOL . PHP_EOL;

			print '- Requesting GPT...' . PHP_EOL;
			$gptService = new GptService;
			//$gptService->setModel('gpt-4');
			$gptService->setModel('gpt-4-1106-preview');
			$showText = $gptService->request($prompt);
			$this->show->processing_cost+= $gptService->getTotalCosts();


			dump($showText);
			$textSplitter = new TextSplitter;
			$groups = $textSplitter->splitTextIntoGroups($showText, 5);
			dump($groups);


//			$prompt = 'Приведенный ниже текст необходимо разбить на 5 смысловых групп для слайдшоу с общим объемом до 90 слов. Каждый слайд должен логически следовать из предыдущего.' . PHP_EOL;
//			$prompt.= 'Для каждой группы напиши короткий и простой для восприятия текст с раскрытием темы.' . PHP_EOL;
//			$prompt.= '- В последнем слайде предложи вопрос-интерактив для зрителей, чтобы они зашли и что-то написали в комментариях к видео-слайдшоу, например: «А как вы считаете, современные ученые смогут запустить спутник к юпитеру? Пишите ответы в комментариях!». Вопрос должен быть плотно связан с темой видео и побуждать зрителей написать комментарий!' . PHP_EOL;
//			$prompt.= '- Если в тексте отсылка к историческим личностям - УКАЗЫВАЙ ПОЛНОЕ ИМЯ И ФАМИЛИЮ, вместо инициалов.'  . PHP_EOL;
//			$prompt.= '- Не используй сокращения, типа г. (год), итд. (и так далее), др (другие). Пишите полные слова.'  . PHP_EOL;
//			$prompt.= '- Используй букву Ё в словах там где она нужна.'  . PHP_EOL;
//			$prompt.= '- Ответ верни в виде JSON массива вида:' . PHP_EOL;
//			$prompt.= '{' . PHP_EOL;
//			$prompt.= '  "episodes": [' . PHP_EOL;
//			$prompt.= '    {"text":"Текст 1"},' . PHP_EOL;
//			$prompt.= '    {"text":"Текст 2"},' . PHP_EOL;
//			$prompt.= '    {"text":"Текст N"}' . PHP_EOL;
//			$prompt.= '  ]' . PHP_EOL;
//			$prompt.= '}' . PHP_EOL . PHP_EOL;
//			$prompt.= 'Текст для слайдшоу: ' . $showText;
//
//			print '- SECOND Propmpt for GPT:' . PHP_EOL;
//			print $prompt . PHP_EOL . PHP_EOL;
//
//			print '- Requesting GPT...' . PHP_EOL;
//			$gptService = new GptService;
//			//$gptService->setModel('gpt-4-1106-preview');
//			//$gptService->asJson = true;
//			$gptService->setModel('gpt-4');
//			$episodesJSON = $gptService->request($prompt);
//			$episodesArray = json_decode($episodesJSON, true);
//
//			if (is_array($episodesArray) == false) {
//				dd($episodesJSON);
//			}
//
//			$this->show->processing_cost+= $gptService->getTotalCosts();
//			$this->show->save();

//			foreach ($episodesArray['episodes'] as $i => $episodesData) {

			// Добавляем название show в начало массива групп
			array_unshift($groups, $this->show->name);

			foreach ($groups as $i => $group) {
				$episode = new Episode;
				$episode->show_id = $this->show->id;
				$episode->text = str_replace("'", '"', $group);

				// Проверяем что в конце текста - точка.
				if (in_array(mb_substr($episode->text, -1), ['.', '?', '!']) == false) {
					$episode->text.= '.';
				}

				//$episode->image_prompt = $episodesData['image_prompt'];
				$episode->save();
				print '------------------' . PHP_EOL;
				print $episode->text . PHP_EOL;
				print '------------------' . PHP_EOL;
			}

			print '- Costs:' . $this->show->processing_cost . PHP_EOL;
			print '✓ Saved!' . PHP_EOL;
			print $this->timestamp() . PHP_EOL;
		}


		$episodesCount = $this->show->episodes()->count();
		print 'Episodes count: ' . $episodesCount . PHP_EOL;


		if ($episodesCount == 0) {
			print 'No episodes created! Exit;' . PHP_EOL;
			exit;
		}


		
		foreach ($this->show->episodes as $i => $episode) {
            
            print $this->timestamp() . PHP_EOL;
            print '------------------------------' . PHP_EOL;
            print 'WORK WITH EPISODE [' . $i . ']' . PHP_EOL;

			print 'Episode text = ' . $episode->text . PHP_EOL;
			print 'Episode stressed_text = ' . $episode->stressed_text . PHP_EOL;
			print 'Episode image_prompt = ' . $episode->image_prompt . PHP_EOL;
			print 'Episode image_url = ' . $episode->image_url . PHP_EOL;
			print 'Episode image = ' . $episode->image . PHP_EOL;
			print 'Episode voice = ' . $episode->voice . PHP_EOL;
			print 'Episode subtitles_video = ' . $episode->subtitles_video . PHP_EOL;
			print 'Episode photo_video = ' . $episode->photo_video . PHP_EOL;
			print 'Episode video = ' . $episode->video . PHP_EOL;


			if ($episode->text != null && $episode->text_to_speech == null) {
				print '### Creating text to speech.' . PHP_EOL;

				$text = mb_strtolower($episode->text);
				$text = $episode->text;
				$text = StressService::getStress($text);

		        // Массив соответствия гласных с ударением и без ударения
		        $replacements = [
		            'а+' => 'А',
		            'А+' => 'А',
		            'е+' => 'Е',
		            'Е+' => 'Е',
		            'ё+' => 'Ё',
		            'Ё+' => 'Ё',
		            'и+' => 'И',
		            'И+' => 'И',
		            'о+' => 'О', 
		            'О+' => 'О', 
		            'у+' => 'У',
		            'У+' => 'У',
		            'ы+' => 'ы',
		            'ы+' => 'ы',
		            'э+' => 'Э',
		            'Э+' => 'Э',
		            'ю+' => 'Ю', 
		            'Ю+' => 'Ю', 
		            'я+' => 'Я',
		            'Я+' => 'Я',
		            'егО' => 'его',
		            'ЕгО' => 'Его',
		            'делАх' => 'делААх'
		        ];

		        // Замена гласных с ударением на заглавные
		        foreach ($replacements as $accented => $capitalized) {
		            $text = str_replace($accented, $capitalized, $text);
		        }

				print '### DONE: ' . $text . PHP_EOL;

				$episode->text_to_speech = $text;
				$episode->save();
			} else {
				print '#############################' . PHP_EOL;
				print '### Episode stressed_text already created' . PHP_EOL;
			}


            // Create Prompt
            if ($episode->image_prompt == null) {
            	print '#############################' . PHP_EOL;
				print '### Creating Prompt.' . PHP_EOL;

				$prompt = 'Я создаю слайдшоу по теме «' . $this->show->name . '»' . PHP_EOL . PHP_EOL;
				$prompt.= 'Создай Prompt на русском языке по которому Dalle создаст Фотореалистичное изображение для слайда.' . PHP_EOL;
				$prompt.= 'Содержимое слайда:'  . PHP_EOL;
				$prompt.= $episode->text . PHP_EOL . PHP_EOL;

				$prompt.= 'Содержимое всей презентации (для контекста):'  . PHP_EOL;
				foreach($this->show->episodes  as $ep) {
					$prompt.= $ep->text . PHP_EOL;
				}

				$prompt.= PHP_EOL;
				$prompt.= 'Если это уместно, в композицию фотографии нужно включить человека или людей.';
				$prompt.= 'Prompt должен конкретным: описывать конкретное событие или действие, которое могло бы происходить в реальности.' . PHP_EOL;
				$prompt.= 'В результате Dalle должен создать ФОТОРЕАЛИСТИЧНОЕ ИЗОБРАЖЕНИЕ, горизонтальную фотографию.' . PHP_EOL;
				//$prompt.= 'В композиции фотографии должно быть только то, что физически могло быть запечетлено на фотографии.' . PHP_EOL;
				//$prompt.= 'На фотографии не должно быть абсурда, галлюцинаций, без антропоморфных персонажей.' . PHP_EOL;
				$prompt.= 'Надписи (текст) на изображении должны быть заблюрены, затерты, так чтобы кривые символы не бросались в глаза.' . PHP_EOL;
				$prompt.= 'Лица известных личностей должны быть скрыты (люди изображены со спины )' . PHP_EOL;
				$prompt.= 'Должен быть безопасный, согласно требованиям Dalle: без крови, убийств, насилия, обнаженки, нарушения авторских прав, оскорбления чувств верующих и прочих запрещенных вещей.' . PHP_EOL;
				$prompt.= 'Объем промпта минимум 50 слов. Не включай в ответ НИЧЕГО кроме prompt для Dalle!' . PHP_EOL;
				$prompt.= 'Укажи точные и детальные описания того, что вы хотите увидеть на изображении. Включите информацию о сцене, объектах, цветовой гамме и освещении.' . PHP_EOL;
				$prompt.= 'Фокусируйся на реальных объектах и сценах.' . PHP_EOL;
				$prompt.= 'Изображение должно выглядеть как настоящаяя черно-белая / сепия фотография. Изображение НЕ ДОЛЖНО выглядеть как рисунок, коллаж или гравюра.' . PHP_EOL;
				$prompt.= 'ПРЕДВАРИТЕЛЬНО ПРОВЕРЬ, ЧТОБЫ ПРОМПТ СООТВЕТСТВОВАЛ CONTENT_POLICY DALLE, ЧТОБЫ НЕ ПОЛУЧИТЬ ОШИБКУ!';

				print '- Propmpt for GPT:' . PHP_EOL;
				print $prompt . PHP_EOL . PHP_EOL;

				print '- Requesting GPT...' . PHP_EOL;
				$gptService = new GptService;
				//$gptService->setModel('gpt-4');
				$gptService->setModel('gpt-4-1106-preview');
				$episode->image_prompt = $gptService->request($prompt);
				$episode->prompt_cost = $gptService->getTotalCosts();
				$episode->save();
            }

			// Creating Image via Dalle
			if ($episode->image_url == null && $episode->image == null && $episode->image_prompt != null) {
				// Creating image!
				print '#############################' . PHP_EOL;
				print '### Creating Image via Dalle.' . PHP_EOL;

				print '- Propmpt for Dalle:' . PHP_EOL;

				//$prompt = 'Изображение должно выглядеть как настоящаяя черно-белая фотография. Фотография должна быть немного потрепанной временем, с потертостями, словно она была сделана в начале 20-го века и долго пролежала в архивах. Лица людей должны быть нормальные.' . PHP_EOL;
				$prompt = 'Изображение должно выглядеть как настоящаяя черно-белая / сепия фотография. Фотография должна быть потрепанной временем, с потертостями, словно она была сделана в начале 20-го века и долго пролежала в архивах.' . PHP_EOL;
				$prompt.= $episode->image_prompt . PHP_EOL;

				print $prompt . PHP_EOL;

				$dalleService = new DalleService;
				//$dalleService->setSize('1792x1024');
				$dalleService->setSize('1024x1024');
				$episode->image_url = $dalleService->request($prompt);
				$episode->save();
				
				print '- Episode image drawn, link saved ' . $episode->image_url . PHP_EOL;
				$episode->image_cost+= $dalleService->getTotalCosts();
				$episode->save();
				print '- Costs: ' . $episode->image_cost . PHP_EOL;
				print '✓ Saved!' . PHP_EOL;
				print $this->timestamp() . PHP_EOL;
			} else {
				print '#############################' . PHP_EOL;
				print '### Episode image already created' . PHP_EOL;
			}


			if ($episode->image_url != null && $episode->image == null) {
				print '#############################' . PHP_EOL;
				print '### Downloading image.' . PHP_EOL;

				$episodeImagePath = $tempDir . '/episode_' . $i . '.png';
                $content = Http::timeout(3600)->get($episode->image_url)->body();
                file_put_contents($episodeImagePath, $content);
				$episode->image = $episodeImagePath;
				$episode->save();
				print '✓ Image downloaded!' . PHP_EOL;

			} else {
				print '#############################' . PHP_EOL;
				print '### Episode image already downloaded' . PHP_EOL;
			}






			// Creating Voice!
			if ($episode->voice == null && $episode->text_to_speech != null) {
				print '#############################' . PHP_EOL;
				print '### Creating Voice' . PHP_EOL;
                $voiceAudioPath = $tempDir . '/voice_' . $i . '.mp3';
                $elevenlabsService = new ElevenlabsService;
                $elevenlabsService->setFilePath($voiceAudioPath);
                $elevenlabsService->setVoiceId($this->show->voice_id);
                $elevenlabsService->setTempo(1.1);
                //$elevenlabsService->setTempo(1);
                if ($i == 0) {
                	$elevenlabsService->setFinalSilence(1);
                } else {
                	$elevenlabsService->setFinalSilence(0.1);
                }
                $elevenlabsService->setStability(1);
                $elevenlabsService->setSimilarityBoost(0.75);
                $elevenlabsService->setStyle(0.33);
                $elevenlabsService->request($episode->text_to_speech);

                $episode->voice = $voiceAudioPath;
                $episode->save();
                print '- Costs: ' . $episode->voice_cost . PHP_EOL;
                print '✓ Saved!' . PHP_EOL;

			} else {
				print '#############################' . PHP_EOL;
				print '### Episode voice already created' . PHP_EOL;
			}



			if ($episode->subtitles_video == null && $episode->text != null && $episode->voice != null) {
				// Creating subtitles!
				print '#############################' . PHP_EOL;
				print '### Creating Subtitles video' . PHP_EOL;

				$framesDir = $tempDir . '/frames_' . $i;
		    	if (is_dir($framesDir) == false) {
		        	mkdir($framesDir);
		        	print 'Frames dir created: ' . $framesDir . PHP_EOL;
		    	}

	            $getID3 = new \getID3;
	            $file = $getID3->analyze($episode->voice);
	            $duration = $file['playtime_seconds'];
	            //print 'Episode voice duration = ' . $duration . PHP_EOL;


				$file = $tempDir . '/subtitles_' . $i . '.mov';
		        // Создание экземпляра класса
		        $subtitleCreator = new VideoSubtitleCreator();
		        $subtitleCreator->setDir($framesDir); // Путь к директории для временных файлов
		        $subtitleCreator->setFinalFile($file); // Путь к итоговому видео файлу
		        $subtitleCreator->setTime($duration);
		        $subtitleCreator->setFps(25);
		        $subtitleCreator->setWidth(1080);
		        $subtitleCreator->setHeight(1920);
		        $subtitleCreator->setWordsInBox(3);
		        $subtitleCreator->setCenterX(540);
		        $subtitleCreator->setCenterY(1400);
		        $subtitleCreator->setBoxColor('#000000');
		        $subtitleCreator->setDefaultTextColor('#FFFFFF');
		        $subtitleCreator->setHighlightedTextColor('#FFFF00');
		        $subtitleCreator->setFontSize(50);
		        $subtitleCreator->setText($episode->text);
		        $subtitleCreator->generate();

		        $episode->subtitles_video = $file;
		        $episode->save();

		        print '✓ Saved!' . PHP_EOL;
			} else {
				print '#############################' . PHP_EOL;
				print '### Episode subtitles video already created' . PHP_EOL;
			}


			if ($episode->photo_video == null && $episode->image != null && $episode->voice != null) {
				// Creating subtitles!
				print '#############################' . PHP_EOL;
				print '### Creating photo video' . PHP_EOL;

	            $getID3 = new \getID3;
	            $file = $getID3->analyze($episode->voice);
	            $duration = $file['playtime_seconds'];
	            //print 'Episode voice duration = ' . $duration . PHP_EOL;


				$photoVideo = $tempDir . '/pan_video_' . $i . '.mov';

				$panAndZoom = new PanAndZoom;
				$panAndZoom->setPhotoPath($episode->image);
				$panAndZoom->setOutputVideoPath($photoVideo);
				$panAndZoom->setFps(25);
				$panAndZoom->setVideoDimensions(1080, 1920);
				$panAndZoom->setTime($duration);


				$r = rand(0,5);
				/*
				FOR 1792x1024
				if ($r == 0) {
					// Slow  LR zoomOut 
					print "Slow  LR zoomOut " . PHP_EOL;
					$panAndZoom->setFromBox(535, 258, 336, 597);
	        		$panAndZoom->setToBox(725, 48, 521, 926);
				} else if ($r == 1) {
					// Slow  RL zoomOut 
					print "Slow  RL zoomOut " . PHP_EOL;
					$panAndZoom->setFromBox(910, 258, 336, 597);
					$panAndZoom->setToBox(535, 48, 521, 926);
				} else if ($r == 2) {
					// Slow  LR zoomIn 
					print "Slow  LR zoomIn " . PHP_EOL;
					$panAndZoom->setFromBox(725, 48, 521, 926);
					$panAndZoom->setToBox(535, 258, 336, 597);
				} else if ($r == 3) {
					// Slow  RL zoomIn
					print "Slow  RL zoomIn" . PHP_EOL;
					$panAndZoom->setFromBox(535, 48, 521, 926);
					$panAndZoom->setToBox(910, 258, 336, 597);
				} else if ($r == 4) {
					// Full LR pan
					print "Full LR pan" . PHP_EOL;
					$panAndZoom->setFromBox(180, 48, 521, 926);
					$panAndZoom->setToBox(1079, 48, 521, 926);
				} else {
					// Full RL pan
					print "Full RL pan" . PHP_EOL;
					$panAndZoom->setToBox(180, 48, 521, 926);
					$panAndZoom->setFromBox(1079, 48, 521, 926);
				}*/


				//FOR 1024x1024
				if ($r == 0) {
					// Slow  LR zoomOut 
					print "Slow  LR zoomOut " . PHP_EOL;
					$panAndZoom->setFromBox(166, 262, 384, 683);
					$panAndZoom->setToBox(292, 10, 564, 1004);
				} else if ($r == 1) {
					// Slow  RL zoomOut 
					print "Slow  RL zoomOut " . PHP_EOL;
					$panAndZoom->setFromBox(472, 262, 384, 683);
					$panAndZoom->setToBox(166, 10, 564, 1004);
				} else if ($r == 2) {
					// Slow  LR zoomIn 
					print "Slow  LR zoomIn " . PHP_EOL;
					$panAndZoom->setFromBox(166, 10, 564, 1004);
					$panAndZoom->setToBox(472, 78, 384, 683);
				} else if ($r == 3) {
					// Slow  RL zoomIn
					print "Slow  RL zoomIn" . PHP_EOL;
					$panAndZoom->setFromBox(292, 10, 564, 1004);
					$panAndZoom->setToBox(166, 78, 384, 683);
				} else if ($r == 4) {
					// Full LR pan
					print "Full LR pan" . PHP_EOL;
					$panAndZoom->setFromBox(16, 80, 521, 926);
					$panAndZoom->setToBox(485, 16, 521, 926);
				} else {
					// Full RL pan
					print "Full RL pan" . PHP_EOL;
					$panAndZoom->setFromBox(16, 16, 521, 926);
					$panAndZoom->setToBox(485, 80, 521, 926);
				}

				$panAndZoom->createVideo();

				$episode->photo_video = $photoVideo;
				$episode->save();
		        print '✓ Saved!' . PHP_EOL;

			} else {
				print '#############################' . PHP_EOL;
				print '### Episode photo video already created' . PHP_EOL;
			}

			if (($episode->video == null || is_file($episode->video) == false) && $episode->photo_video != null && $episode->subtitles_video != null &&  $episode->voice != null) {
				// Creating subtitles!
				print '#############################' . PHP_EOL;
				print '### Combine photo and subtitles video and audio' . PHP_EOL;

				$finalVideoPath = $tempDir . '/episode_' . $i . '.mp4';
				$tmpVideo = $tempDir . '/pan_with_subtitles_' . $i . '.mp4';
				FfmpegService::overlaySubtitles($episode->photo_video, $episode->subtitles_video, $tmpVideo);
				FfmpegService::combineVideoWithAudio($tmpVideo, $episode->voice, $finalVideoPath, 2);

				try {
					//unlink($tmpVideo);
				} catch (\Throwable $e) { }


				$episode->video = $finalVideoPath;
				$episode->save();
		        print '✓ Saved!' . PHP_EOL;

			}



		}


		if ($this->show->final_video == null OR is_file($this->show->final_video) == false) {
//		if ($this->show->final_video == null) {

			print '#############################' . PHP_EOL;
			print '### Creating FINAL VIDEO!!!' . PHP_EOL;
			sleep(2);

			$videosPaths = [];

			foreach ($this->show->episodes()->orderBy('id')->get() as $i => $episode) {
				$videosPaths[]= $episode->video;
			}

			$resultVideoPath = storage_path('app/final_video/ussr/show_' . $this->show->id . '.mp4');
			$tmpVideo = '/tmp/' . uniqid('show_' . $this->show->id . '_video_') . '.mp4';
			FfmpegService::concatenateVideos($videosPaths, $tmpVideo);

			if ($this->show->music != null) {
				FfmpegService::combineVideoWithAudio($tmpVideo, resource_path('music/' . $this->show->music), $resultVideoPath, 0.2);
				try {
					unlink($tmpVideo);
				} catch (\Throwable $e) { }
			} else {
				rename($tmpVideo, $resultVideoPath);
			}

			$humanVideoName = storage_path('app/final_video/ussr/' . 'show_' . $this->show->id . ' - ' . $this->show->name . '.mp4');
			rename($resultVideoPath, $humanVideoName);
			$this->show->status = 'done';
			$this->show->final_video = $humanVideoName;
			$this->show->save();

		} else {
				print '#############################' . PHP_EOL;
				print '### FINAL VIDEO already created' . PHP_EOL;
		}

		print '### Done. Exit;' . PHP_EOL;
		print '#############################' . PHP_EOL;
	}


	private function timestamp()
	{
		return '[' . date('Y-m-d H:i:s') . '], ' . round(microtime(true) - $this->microtime, 4) . ' sec from strat';
	}



}
