<?php
if (isset($field['raw'])) {
    $string = $field['raw'];
} else {
    $value = 1;
    $value = Cake\Utility\Hash::extract($data, $field['path']);
    $string = empty($value[0]) ? '' : $value[0];
}
if (!empty($field['url'])) {
    if (!empty($field['url_vars'])) {
        if (!is_array($field['url_vars'])) {
            $field['url_vars'] = [$field['url_vars']];
        }
        foreach ($field['url_vars'] as $k => $path) {
            $field['url'] = str_replace('{{' . $k . '}}', $this->Hash->extract($data, $path)[0], $field['url']);
        }
    }
    if (substr($field['url'], 0, 4) === 'http') {
        $baseurl = '';
    }
    $string = sprintf(
        '<a href="%s%s">%s</a>',
        $baseurl,
        h($field['url']),
        $string
    );
}
echo $string;
