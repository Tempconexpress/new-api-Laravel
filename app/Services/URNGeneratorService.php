<?php namespace App\Services;

class URNGeneratorService
{
    public function generate_URN_specific($arr = [])
    {
        if (!isset($arr['WL']) || $arr['WL'] == '') {
            $arr['WL'] = 4;
        }

        if (!isset($arr['WR']) || $arr['WR'] == '') {
            $arr['WR'] = 4;
        }

        $digits = ['2', '3', '4', '5', '6', '7', '8', '9'];
        $alphas = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K', 'M', 'N', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];

        $urn = '';
        $maxA = count($alphas) - 1;
        $maxD = count($digits) - 1;

        if (isset($arr['for']) && $arr['for'] === 'booking') {
            for ($i = 0; $i < $arr['WL']; $i++) {
                $urn .= $alphas[mt_rand(0, $maxA)];
            }

            $urn .= (date('m') + mt_rand(10, 80)) . @$arr['TXT'];

            for ($i = 0; $i < $arr['WR']; $i++) {
                $urn .= $digits[mt_rand(0, $maxD)];
            }

            $urn = (date('d') + mt_rand(10, 80)) . $urn;
        } elseif (isset($arr['for']) && $arr['for'] === 'company') {
            $urn .= @$arr['TXT'] . (date('m') + mt_rand(10, 80)) . (date('d') + mt_rand(10, 60)) . mt_rand(10, 99);
        } elseif (isset($arr['date'])) {
            $urn .= @$arr['TXT'] . (date('m') + mt_rand(10, 80)) . (date('d') + mt_rand(10, 60)) . mt_rand(10, 99);
        } elseif (isset($arr['type']) && $arr['type'] === 'digits') {
            $urn .= @$arr['TXT'] . $digits[mt_rand(0, $maxA)];
        } elseif (isset($arr['type']) && $arr['type'] === 'alpha-a') {
            for ($i = 0; $i < $arr['WL']; $i++) {
                $urn .= $alphas[mt_rand(0, $maxA)];
            }
        } elseif (isset($arr['type']) && $arr['type'] === 'alpha') {
            $urn = @$arr['TXT'];
            for ($i = 0; $i < $arr['WL']; $i++) {
                $urn = $alphas[mt_rand(0, $maxA)] . $urn;
            }
            for ($i = 0; $i < $arr['WR']; $i++) {
                $urn = $urn . $alphas[mt_rand(0, $maxA)];
            }
        }

        return $urn;
    }
}
