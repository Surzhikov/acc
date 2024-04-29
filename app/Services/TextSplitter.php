<?php

namespace App\Services;

class TextSplitter {

    public function splitTextIntoGroups($text, $groupsCount) {
        $sentences = $this->splitIntoSentences($text);

        if (count($sentences) <= $groupsCount) {
            return $sentences;
        }

        $lengths = array_map(function ($s) { return mb_strlen($s, 'UTF-8'); }, $sentences);
        return $this->distributeSentences($sentences, $lengths, $groupsCount);
    }

    private function splitIntoSentences($text) {
        // Замена точек в инициалах и сокращениях на специальный маркер
        $text = preg_replace('/\b([A-ZА-ЯЁ])\.\s*/u', '$1<dot> ', $text);
        $text = preg_replace('/\.{2,}/u', '…', $text); // Замена многоточий

        // Разбиение на предложения
        $sentences = preg_split('/(?<=[.!?…;])\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        // Восстановление точек в инициалах и сокращениях
        return array_map(function ($sentence) {
            return str_replace('<dot>', '.', $sentence);
        }, $sentences);
    }

    private function distributeSentences($sentences, $lengths, $groupsCount) {
        $groups = array_fill(0, $groupsCount, '');
        $groupLengths = array_fill(0, $groupsCount, 0);
        $totalLength = array_sum($lengths);
        $averageLength = $totalLength / $groupsCount;

        $currentGroupIndex = 0;

        foreach ($sentences as $i => $sentence) {
            if ($currentGroupIndex < $groupsCount - 1 && 
                $groupLengths[$currentGroupIndex] + $lengths[$i] > $averageLength) {
                $currentGroupIndex++;
            }

            $groups[$currentGroupIndex] .= ($groups[$currentGroupIndex] === '' ? '' : ' ') . $sentence;
            $groupLengths[$currentGroupIndex] += $lengths[$i];
        }

        return $groups;
    }
}
