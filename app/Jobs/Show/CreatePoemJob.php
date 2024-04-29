<?php

namespace App\Jobs\Show;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Services\GptService;
use App\Services\DalleService;

use App\Models\Show;
use App\Models\Episode;

class CreatePoemJob implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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

        $tempDir = storage_path('app/show_' . $this->show->id);
        if (is_dir($tempDir) == false) {
            mkdir($tempDir);
            print '✓ TempDir created' . PHP_EOL;
        }

		$episodesCount = $this->show->episodes()->count();

		if ($episodesCount == 0) {
			print '→ Creating episodes...' . PHP_EOL;
			$episodesTexts = explode("\r\n\r\n", $this->show->content);
			foreach ($episodesTexts as $text) {
				$episode = new Episode;
				$episode->show_id = $this->show->id;
				$episode->text = $text;
				$episode->save();
				print '------------------' . PHP_EOL;
				print $episode->text . PHP_EOL;
			}
			
			print '------------------' . PHP_EOL;
			print '✓ Episodes created!' . PHP_EOL;
		}


		$episodesCount = $this->show->episodes()->count();
		print 'Episodes count: ' . $episodesCount . PHP_EOL;

		if ($episodesCount == 0) {
			print 'No episodes created! Exit;' . PHP_EOL;
			exit;
		}



		foreach ($this->show->episodes as $i => $episode) {
            
            print $this->timestamp() . PHP_EOL;

			// Creating Propmpt for Dalle via GPT
			if ($episode->image_prompt == null) {

				print PHP_EOL . PHP_EOL;
				print '### Creating Propmpt for Dalle via GPT.' . PHP_EOL;

				$prompt = 'Я создаю анимированное слайд-шоу по стихотворению ' . $this->show->name . '.' . PHP_EOL . PHP_EOL;
				$prompt.= 'Ниже будет приведен фрагмент стихотворения, для которого необходимо создать максимально релевантное изображение.'. PHP_EOL;
				$prompt.= 'На изображение должны быть отражены персонажи и события, описанные во фрагменте стихотворения.' . PHP_EOL;
				$prompt.= 'Cоздай prompt, по которому Dalle сгенерирует изображение для фрагмента стихотворения.' . PHP_EOL;
				$prompt.= 'Prompt для Dalle должен быть подробный: минимальный объем промпта для Dalle 30 слов.' . PHP_EOL;
				$prompt.= 'Prompt для Dalle должен быть безопасный, согласно требованиям Dalle: без крови, убийств, насилия, обнаженки, нарушения авторских прав и прочих запрещенных вещей.' . PHP_EOL;
				$prompt.= 'Prompt для Dalle должен быть на Английском языке!' . PHP_EOL . PHP_EOL;
				$prompt.= 'ОПИШИ КАЖДОГО ПЕРСОНАЖА, ПРИСУТСТВУЮЩЕГО В ФРАГМЕНТЕ ТЕКСТА В 10 СЛОВАХ, так чтобы один и тот же персонаж на разных слайдах выглядел максимально похожим.' . PHP_EOL;
				$prompt.= 'В ответе верни ТОЛЬКО ТЕКСТ Prompt для Dalle БЕЗ ЛЮБЫХ ДОПОЛНИТЕЛЬНЫХ ДАННЫХ!!!' . PHP_EOL . PHP_EOL;
				$prompt.= 'Вот текст фрагмента стихотворения:' . PHP_EOL;
				$prompt.= $episode->text;

				print '- Propmpt for GPT:' . PHP_EOL;
				print $prompt . PHP_EOL . PHP_EOL;

				print '- Requesting GPT...' . PHP_EOL;
				$gptService = new GptService;
				$episode->image_prompt = $gptService->request($prompt);
				$episode->prompt_cost = $gptService->getTotalCosts();
				$episode->save();

				print '✓ Done!' . PHP_EOL;
				print '- Prompt for Dalle: ' . $episode->image_prompt . PHP_EOL;
				print '- Costs:' . $episode->prompt_cost . PHP_EOL;
				print '✓ Saved!' . PHP_EOL;
				print $this->timestamp() . PHP_EOL;
			}



			// Creating Image via Dalle
			if ($episode->image == null) {
				// Creating image!
				print PHP_EOL . PHP_EOL;
				print '### Creating Image via Dalle.' . PHP_EOL;

				print '- Propmpt for Dalle:' . PHP_EOL;
				if ($this->show->type == 'poems') {
					$prompt = $episode->image_prompt . PHP_EOL;
					//$prompt.= 'Стиль изображения: детская книжка, спокойные пастельные тона. Не используй текст на изображении.' . PHP_EOL;
					$prompt.= 'The art should have the warmth of a school book, with a rich but muted color palette that evokes a cozy feel. The images are aimed at children 5-10 years old.' . PHP_EOL;
				} else {
					throw new \Exception("Unknown show type");
					exit;
				}
				print $prompt . PHP_EOL;

				$episodeImagePath = $tempDir . '/episode_' . $i . '.png';

				$dalleService = new DalleService;
				$dalleService->request($prompt, $episodeImagePath);
				print '- Episode image drawn and stored ' . $episodeImagePath . PHP_EOL;

				$episode->image = $episodeImagePath;
				$episode->image_cost+= $dalleService->getTotalCosts();
				$episode->save();
				print '- Costs: ' . $episode->image_cost . PHP_EOL;
				print '✓ Saved!' . PHP_EOL;
				print $this->timestamp() . PHP_EOL;
			}


		}


	}


	private function timestamp()
	{
		return '[' . date('Y-m-d H:i:s') . '], ' . round(microtime(true) - $this->microtime, 4) . ' sec from strat';
	}

}
