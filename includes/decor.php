<?php
/**
 * Decoración: animales arriba + elementos CSS por página.
 */
if (!function_exists('ldl_animals_basenames')) {
    function ldl_animals_basenames() {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        $dir = dirname(__DIR__) . '/assets/animals';
        $glob = glob($dir . '/*.png');
        if ($glob === false) {
            $cached = array();
            return $cached;
        }
        sort($glob, SORT_STRING);
        $cached = array();
        foreach ($glob as $path) {
            $cached[] = basename($path);
        }
        return $cached;
    }

    function ldl_decor_pick_unique_indices($nFiles, $need, $seed) {
        if ($nFiles === 0 || $need <= 0) {
            return array();
        }
        $indices = array();
        $h = crc32((string) $seed);
        for ($k = 0; $k < $need; $k++) {
            $h = crc32($h . 'x' . $k);
            $i = $h % $nFiles;
            $guard = 0;
            while (in_array($i, $indices, true) && $guard < $nFiles + 5) {
                $i = ($i + 1) % $nFiles;
                $guard++;
            }
            $indices[] = $i;
        }
        return $indices;
    }

    function ldl_decor_u32($pageId, $salt) {
        return crc32((string) $pageId . '|' . $salt) & 0x7fffffff;
    }

    function ldl_decor_render($pageId) {
        $files = ldl_animals_basenames();
        $n = count($files);
        $positions = array('p1', 'p2', 'p3', 'p4', 'p5');
        echo '<div class="page-decor page-decor--top" aria-hidden="true">';

        if ($n > 0) {
            $need = min(5, $n);
            $idxs = ldl_decor_pick_unique_indices($n, $need, $pageId . '-anim');
            foreach ($idxs as $j => $fi) {
                $basename = $files[$fi];
                $pos = isset($positions[$j]) ? $positions[$j] : 'p' . ($j + 1);
                $delay = $j * 0.7;
                echo '<div class="page-decor-float page-decor-float--' . $pos . '" style="animation-delay: ' . $delay . 's">';
                echo '<img class="page-decor-img" src="' . htmlspecialchars(ldl_asset('assets/animals/' . $basename), ENT_QUOTES, 'UTF-8') . '" alt="" width="120" height="120" loading="lazy" decoding="async">';
                echo '</div>';
            }
        }

        $seed = ldl_decor_u32($pageId, 'spark');
        echo '<span class="page-decor-extra page-decor-extra--spark" style="animation-delay:' . (($seed % 5) * 0.15) . 's"></span>';
        echo '<span class="page-decor-extra page-decor-extra--blob" style="animation-delay:' . ((($seed >> 3) % 5) * 0.2) . 's"></span>';
        echo '<span class="page-decor-extra page-decor-extra--ring" style="animation-delay:' . ((($seed >> 6) % 5) * 0.25) . 's"></span>';

        echo '</div>';

        echo '<div class="page-decor page-decor--field" aria-hidden="true">';
        $count = 22;
        for ($i = 0; $i < $count; $i++) {
            $h = ldl_decor_u32($pageId, 'bit' . $i);
            $top = 3 + ($h % 92);
            $side = ($h >> 7) % 100;
            $size = 6 + ($h % 36);
            $op = 0.05 + (($h >> 14) % 18) / 100;
            if ($op > 0.22) {
                $op = 0.22;
            }
            $delay = ($h % 80) / 10;
            $dur = 9 + ($h % 18);
            $rot = $h % 360;
            $variant = $i % 7;

            $pos = 'top:' . $top . '%;';
            if (($i % 4) === 1 || ($i % 4) === 2) {
                $pos .= 'right:' . (2 + ($side % 45)) . '%;left:auto;';
            } else {
                $pos .= 'left:' . (3 + ($side % 50)) . '%;';
            }

            echo '<span class="page-decor-bit page-decor-bit--v' . $variant . '" style="' . $pos
                . 'width:' . $size . 'px;height:' . $size . 'px;opacity:' . $op
                . ';animation-delay:' . $delay . 's;--dur:' . $dur . 's;--rot:' . $rot . 'deg"></span>';
        }
        echo '</div>';

        if ($n > 0) {
            $scatterN = 16;
            echo '<div class="page-decor page-decor--scatter" aria-hidden="true">';
            for ($si = 0; $si < $scatterN; $si++) {
                $h = ldl_decor_u32($pageId, 'scatter' . $si);
                $fi = (int) ($h % $n);
                $basename = $files[$fi];
                $top = 6 + ($h % 82);
                $side = ($h >> 9) % 88;
                $useRight = (($h >> 17) & 1) === 1;
                $w = 38 + ($h % 56);
                $op = 0.2 + (($h >> 21) % 20) / 100;
                if ($op > 0.44) {
                    $op = 0.44;
                }
                $delay = ($h % 70) / 10;
                $dur = 10 + ($h % 16);
                $rot = (($h % 21) - 10);

                $style = 'top:' . $top . '%;';
                if ($useRight) {
                    $style .= 'right:' . (2 + ($side % 42)) . '%;left:auto;';
                } else {
                    $style .= 'left:' . (2 + ($side % 46)) . '%;';
                }
                $style .= 'opacity:' . $op . ';animation-delay:' . $delay . 's;--dur:' . $dur . 's;--rot:' . $rot . 'deg;width:' . $w . 'px;';

                echo '<div class="page-decor-scatter" style="' . $style . '">';
                echo '<img src="' . htmlspecialchars(ldl_asset('assets/animals/' . $basename), ENT_QUOTES, 'UTF-8') . '" alt="" width="128" height="128" loading="lazy" decoding="async">';
                echo '</div>';
            }
            echo '</div>';
        }
    }
}
