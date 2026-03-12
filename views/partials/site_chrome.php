<?php

if (!function_exists('site_default_about_html')) {
    function site_default_about_html() {
        return 'Das Parlaments-Anfragen Dashboard analysiert parlamentarische Anfragen aus Nationalrat und Bundesrat.<br><br>Es macht sichtbar, wie oft, von wem und in welchen Mustern Anfragen eingebracht werden.';
    }
}

if (!function_exists('site_default_notice_html')) {
    function site_default_notice_html() {
        return 'Hinweis: Diese Plattform ist experimentell. Fehler koennen vorkommen.';
    }
}

if (!function_exists('site_render_floating_header')) {
    function site_render_floating_header(array $options = []) {
        ?>
<header class="subsite-header subsite-header-empty" aria-hidden="true"></header>
        <?php
    }
}

if (!function_exists('site_render_bar_header')) {
    function site_render_bar_header(array $options = []) {
        ?>
<header class="subsite-header subsite-header-empty" aria-hidden="true"></header>
        <?php
    }
}

if (!function_exists('site_render_footer')) {
    function site_render_footer(array $options = []) {
        $aboutTitle = isset($options['aboutTitle']) ? $options['aboutTitle'] : 'Ueber das Projekt';
        $aboutHtml = isset($options['aboutHtml']) ? $options['aboutHtml'] : site_default_about_html();
        $noticeHtml = array_key_exists('noticeHtml', $options) ? $options['noticeHtml'] : site_default_notice_html();
        $copyright = isset($options['copyright']) ? $options['copyright'] : ('© ' . date('Y') . ' PARLAMENTS-ANFRAGEN DASHBOARD');
        $links = isset($options['links']) ? $options['links'] : [];
        $rightLines = isset($options['rightLines']) ? $options['rightLines'] : [];
        $statusLabel = isset($options['statusLabel']) ? $options['statusLabel'] : 'SYSTEM OPERATIONAL';
        ?>
<footer class="subsite-footer">
    <div class="container-custom subsite-footer-inner">
        <div class="subsite-footer-grid">
            <div>
                <h3 class="subsite-footer-title"><?php echo htmlspecialchars($aboutTitle); ?></h3>
                <p class="subsite-footer-text"><?php echo $aboutHtml; ?></p>
                <?php if ($noticeHtml !== null && $noticeHtml !== ''): ?>
                    <p class="subsite-footer-notice"><?php echo $noticeHtml; ?></p>
                <?php endif; ?>
                <p class="subsite-footer-copy"><?php echo htmlspecialchars($copyright); ?></p>
                <?php if (!empty($links)): ?>
                    <div class="subsite-footer-links">
                        <?php foreach ($links as $link): ?>
                            <?php
                            $href = isset($link['href']) ? $link['href'] : '#';
                            $label = isset($link['label']) ? $link['label'] : '';
                            $extraClass = isset($link['class']) ? $link['class'] : '';
                            ?>
                            <a href="<?php echo htmlspecialchars($href); ?>" class="subsite-footer-link <?php echo htmlspecialchars($extraClass); ?>"><?php echo htmlspecialchars($label); ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="subsite-footer-meta">
                <?php foreach ($rightLines as $line): ?>
                    <p><?php echo htmlspecialchars($line); ?></p>
                <?php endforeach; ?>
                <div class="subsite-status">
                    <span class="subsite-status-dot" aria-hidden="true"></span>
                    <span class="subsite-status-text"><?php echo htmlspecialchars($statusLabel); ?></span>
                </div>
            </div>
        </div>
    </div>
</footer>
        <?php
    }
}
