<?php

if (!function_exists('site_default_about_html')) {
    function site_default_about_html() {
        return 'Das Parlaments-Anfragen Dashboard analysiert parlamentarische Anfragen aus Nationalrat und Bundesrat.<br><br>Es macht sichtbar, wie oft, von wem und in welchen Mustern Anfragen eingebracht werden.';
    }
}

if (!function_exists('site_default_notice_html')) {
    function site_default_notice_html() {
        return 'Hinweis: Diese Plattform ist experimentell. Fehler können vorkommen.';
    }
}

if (!function_exists('site_render_floating_header')) {
    function site_render_floating_header(array $options = []) {
        $shortLabel = isset($options['shortLabel']) ? $options['shortLabel'] : 'PAD';
        $longLabel = isset($options['longLabel']) ? $options['longLabel'] : 'Parlaments-Anfragen Dashboard';
        ?>
<header class="w-full absolute top-0 z-50 bg-transparent">
    <div class="container mx-auto px-6 h-16 flex justify-between items-center">
        <a href="index.php" class="flex items-center gap-3 group">
            <div class="w-3 h-3 bg-white group-hover:bg-green-500 transition-colors duration-300"></div>
            <span class="font-bebas text-xl md:text-2xl tracking-widest text-white mt-1">
                <span class="md:hidden"><?php echo htmlspecialchars($shortLabel); ?></span>
                <span class="hidden md:inline"><?php echo htmlspecialchars($longLabel); ?></span>
            </span>
        </a>
    </div>
</header>
        <?php
    }
}

if (!function_exists('site_render_bar_header')) {
    function site_render_bar_header(array $options = []) {
        $brandText = isset($options['brandText']) ? $options['brandText'] : 'PARLAMENTS-ANFRAGEN DASHBOARD';
        $brandHref = isset($options['brandHref']) ? $options['brandHref'] : 'index.php';
        $navLinks = isset($options['navLinks']) ? $options['navLinks'] : [];
        $defaultNavClass = 'text-sm font-mono text-gray-400 hover:text-white transition-colors';
        ?>
<header class="bg-black border-b border-white py-6">
    <div class="container-custom">
        <div class="flex items-center justify-between">
            <a href="<?php echo htmlspecialchars($brandHref); ?>" class="text-2xl font-bebas tracking-wider hover:text-gray-300 transition-colors">
                <?php echo htmlspecialchars($brandText); ?>
            </a>
            <?php if (!empty($navLinks)): ?>
                <nav class="flex gap-6">
                    <?php foreach ($navLinks as $link): ?>
                        <?php
                        $href = isset($link['href']) ? $link['href'] : '#';
                        $label = isset($link['label']) ? $link['label'] : '';
                        $class = isset($link['class']) ? $link['class'] : $defaultNavClass;
                        ?>
                        <a href="<?php echo htmlspecialchars($href); ?>" class="<?php echo htmlspecialchars($class); ?>"><?php echo htmlspecialchars($label); ?></a>
                    <?php endforeach; ?>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</header>
        <?php
    }
}

if (!function_exists('site_render_footer')) {
    function site_render_footer(array $options = []) {
        $aboutTitle = isset($options['aboutTitle']) ? $options['aboutTitle'] : 'Über das Projekt';
        $aboutHtml = isset($options['aboutHtml']) ? $options['aboutHtml'] : site_default_about_html();
        $noticeHtml = array_key_exists('noticeHtml', $options) ? $options['noticeHtml'] : site_default_notice_html();
        $copyright = isset($options['copyright']) ? $options['copyright'] : ('© ' . date('Y') . ' PARLAMENTS-ANFRAGEN DASHBOARD');
        $links = isset($options['links']) ? $options['links'] : [];
        $rightLines = isset($options['rightLines']) ? $options['rightLines'] : [];
        $statusLabel = isset($options['statusLabel']) ? $options['statusLabel'] : 'SYSTEM OPERATIONAL';
        $statusDotClass = isset($options['statusDotClass']) ? $options['statusDotClass'] : 'bg-green-600';
        $statusTextClass = isset($options['statusTextClass']) ? $options['statusTextClass'] : 'text-green-600';
        $defaultLinkClass = 'text-xs font-mono text-gray-500 hover:text-white transition-colors underline';
        ?>
<footer class="bg-black border-t border-white py-8 md:py-12 mt-auto">
    <div class="container-custom">
        <div class="flex flex-col md:flex-row justify-between items-start gap-8">
            <div class="max-w-md">
                <h3 class="text-sm font-bold text-white mb-4 uppercase tracking-wider"><?php echo htmlspecialchars($aboutTitle); ?></h3>
                <p class="text-xs text-gray-500 leading-relaxed font-sans mb-4"><?php echo $aboutHtml; ?></p>
                <?php if ($noticeHtml !== null && $noticeHtml !== ''): ?>
                    <div class="text-xs text-yellow-600 leading-relaxed font-sans mb-4 italic"><?php echo $noticeHtml; ?></div>
                <?php endif; ?>
                <div class="text-xs font-mono text-gray-600"><?php echo htmlspecialchars($copyright); ?></div>
                <?php if (!empty($links)): ?>
                    <div class="mt-2 space-x-4">
                        <?php foreach ($links as $link): ?>
                            <?php
                            $href = isset($link['href']) ? $link['href'] : '#';
                            $label = isset($link['label']) ? $link['label'] : '';
                            $class = isset($link['class']) ? $link['class'] : $defaultLinkClass;
                            ?>
                            <a href="<?php echo htmlspecialchars($href); ?>" class="<?php echo htmlspecialchars($class); ?>"><?php echo htmlspecialchars($label); ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="text-left md:text-right w-full md:w-auto">
                <?php foreach ($rightLines as $line): ?>
                    <div class="text-xs font-mono text-gray-500 mb-2"><?php echo htmlspecialchars($line); ?></div>
                <?php endforeach; ?>
                <div class="flex items-center justify-start md:justify-end gap-2 mt-4">
                    <div class="w-2 h-2 rounded-full <?php echo htmlspecialchars($statusDotClass); ?>"></div>
                    <span class="text-xs font-mono <?php echo htmlspecialchars($statusTextClass); ?>"><?php echo htmlspecialchars($statusLabel); ?></span>
                </div>
            </div>
        </div>
    </div>
</footer>
        <?php
    }
}
