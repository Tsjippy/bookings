<?php

error_log(print_r($attributes, true));

return '<pre>' . esc_html(wp_json_encode($attributes)) . '</pre>';