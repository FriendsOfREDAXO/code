<?php

$addon = rex_addon::get('code');
echo rex_view::title($addon->i18n('code_title'));
rex_be_controller::includeCurrentPageSubPath();