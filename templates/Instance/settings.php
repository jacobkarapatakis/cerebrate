<?php
// debug($settings);
// debug($settingsProvider);
// debug($notices);
$mainNoticeHeading = [
    'critical' => __('Your Cerebrate instance requires immediate attention.'),
    'warning' => __('Issues found, it is recommended that you resolve them.'),
    'info' => __('There are some optional settings that are incorrect or not set.'),
];
$noticeDescriptionPerLevel = [
    'critical' => __('Cerebrate will not operate correctly or will be unsecure until these issues are resolved.'),
    'warning' => __('Some of the features of Cerebrate cannot be utilised until these issues are resolved.'),
    'info' => __('There are some optional tweaks that could be done to improve the looks of your Cerebrate instance.'),
];
$headingPerLevel = [
    'critical' => __('Critical settings'),
    'warning' => __('Warning settings'),
    'info' => __('Info settings'),
];
$variantFromSeverity = [
    'critical' => 'danger',
    'warning' => 'warning',
    'info' => 'info',
];
$this->set('variantFromSeverity', $variantFromSeverity);

$alertVariant = 'info';
$alertBody = '';
$skipHeading = false;
$tableItems = [];
foreach (array_keys($mainNoticeHeading) as $level) {
    if(!empty($notices[$level])) {
        $variant = $variantFromSeverity[$level];
        if (!$skipHeading) {
            $alertBody .= sprintf('<h5 class="alert-heading">%s</h5>', $mainNoticeHeading[$level]);
            $alertVariant = $variant;
            $skipHeading = true;
        }
        $tableItems[] = [
            'severity' => $headingPerLevel[$level],
            'issues' => count($notices[$level]),
            'badge-variant' => $variant,
            'description' => $noticeDescriptionPerLevel[$level],
        ];
    }
}
$alertBody .= $this->Bootstrap->table([
    'small' => true,
    'striped' => false,
    'hover' => false,
    'borderless' => true,
    'bordered' => false,
    'tableClass' => 'mb-0'
], [
    'fields' => [
        ['key' => 'severity', 'label' => __('Severity')],
        ['key' => 'issues', 'label' => __('Issues'), 'formatter' => function($count, $row) {
            return $this->Bootstrap->badge([
                'variant' => $row['badge-variant'],
                'text' => $count
            ]);
        }],
        ['key' => 'description', 'label' => __('Description')]
    ],
    'items' => $tableItems,
]);
$settingNotice = $this->Bootstrap->alert([
    'dismissible' => false,
    'variant' => $alertVariant,
    'html' => $alertBody
]);
$settingNotice = sprintf('<div class="mt-3">%s</div>', $settingNotice);
$this->set('settingNotice', $settingNotice);
$settingTable = genLevel0($settingsProvider, $this);
?>
<div class="px-5">
    <div class="mb-3">
        <select id="search-settings" class="d-block w-100" aria-describedby="<?= __('Search setting input') ?>"><option></option></select>
    </div>
    <?= $settingTable; ?>
</div>

<?php
function genLevel0($settingsProvider, $appView)
{
    $content0 = [];
    $level0 = array_keys($settingsProvider);
    foreach ($settingsProvider as $level1Name => $level1Setting) {
        if (!empty($level1Setting)) {
            $content0[] = genLevel1($level1Setting, $appView);
        } else {
            $content0[] = __('No Settings available yet');
        }
    }
    array_unshift($level0, __('Setting Diagnostic'));
    array_unshift($content0, $appView->get('settingNotice'));
    $tabsOptions0 = [
        // 'vertical' => true,
        // 'vertical-size' => 2,
        'card' => false,
        'pills' => false,
        'justify' => 'center',
        'nav-class' => ['settings-tabs'],
        'data' => [
            'navs' => $level0,
            'content' => $content0
        ]
    ];
    $table0 = $appView->Bootstrap->tabs($tabsOptions0);
    return $table0;
}

function genLevel1($level1Setting, $appView)
{
    $content1 = [];
    $nav1 = [];
    foreach ($level1Setting as $level2Name => $level2Setting) {
        if (!empty($level2Setting)) {
            $content1[] = genLevel2($level2Name, $level2Setting, $appView);
        } else {
            $content1[] = '';
        }
        $nav1[$level2Name] = array_filter( // only show grouped settings
            array_keys($level2Setting),
            function ($settingGroupName) use ($level2Setting) {
                return !isLeaf($level2Setting[$settingGroupName]);
            }
        );
    }
    $contentHtml = implode('', $content1);
    $scrollspyNav = genScrollspyNav($nav1);
    $mainPanelHeight = 'calc(100vh - 8px - 42px - 1rem - 56px - 38px - 1rem)';
    $container =  '<div class="d-flex">';
    $container .=   "<div class=\"\" style=\"flex: 0 0 10em;\">{$scrollspyNav}</div>";
    $container .=   "<div data-spy=\"scroll\" data-target=\"#navbar-scrollspy-setting\" data-offset=\"25\" style=\"height: {$mainPanelHeight}\" class=\"p-3 overflow-auto position-relative flex-grow-1\">{$contentHtml}</div>";
    $container .= '</div>';
    return $container;
}

function genLevel2($level2Name, $level2Setting, $appView)
{
    foreach ($level2Setting as $level3Name => $level3Setting) {
        if (!empty($level3Setting)) {
            $level3 = genLevel3($level2Name, $level3Name, $level3Setting, $appView);
            $content2[] = sprintf('<div id="%s">%s</div>', sprintf('sp-%s', h($level2Name)), $level3);
        } else {
            $content2[] = '';
        }
    }
    return implode('', $content2);
}

function genLevel3($level2Name, $settingGroupName, $setting, $appView)
{
    $settingGroup = '';
    if (isLeaf($setting)) {
        $tmp = genSingleSetting($settingGroupName, $setting, $appView);
        $settingGroup = "<div>{$tmp}</div>";
    } else {
        $tmpID = sprintf('sp-%s-%s', h($level2Name), h($settingGroupName)); 
        $settingGroup .= sprintf('<h4 id="%s"><a class="text-reset text-decoration-none" href="#%s">%s</a></h4>', $tmpID, $tmpID, h($settingGroupName));
        $groupIssueSeverity = false;
        foreach ($setting as $singleSettingName => $singleSetting) {
            $tmp = genSingleSetting($singleSettingName, $singleSetting, $appView);
            $settingGroup .= sprintf('<div class="ml-3">%s</div>', $tmp);
            if (!empty($singleSetting['error'])) {
                $settingVariant = $appView->get('variantFromSeverity')[$singleSetting['severity']];
                if ($groupIssueSeverity != 'danger') {
                    if ($groupIssueSeverity != 'warning') {
                        $groupIssueSeverity = $settingVariant;
                    }
                }
            }
        }
        $settingGroup = $appView->Bootstrap->genNode('div', [
            'class' => [
                'shadow',
                'p-2',
                'mb-4',
                'rounded',
                (!empty($groupIssueSeverity) ? "callout callout-${groupIssueSeverity}" : ''),
                ($appView->get('darkMode') ? 'bg-dark' : 'bg-light')
            ],
        ], $settingGroup);
    }
    return $settingGroup;
}

function genSingleSetting($settingName, $setting, $appView)
{
    $dependsOnHtml = '';
    if (!empty($setting['dependsOn'])) {
        $dependsOnHtml = $appView->Bootstrap->genNode('span', [
        ], $appView->Bootstrap->genNode('sup', [
            'class' => [
                $appView->FontAwesome->getClass('info'),
                'ml-1',
            ],
            'title' => __('This setting depends on the validity of: {0}', h($setting['dependsOn']))
        ]));
    }
    $settingId = str_replace('.', '_', $settingName);
    $label = $appView->Bootstrap->genNode('label', [
        'class' => ['font-weight-bolder', 'mb-0'],
        'for' => $settingId
    ], h($setting['name']) . $dependsOnHtml);
    $description = '';
    if (!empty($setting['description'])) {
        $description = $appView->Bootstrap->genNode('small', [
            'class' => ['form-text', 'text-muted', 'mt-0'],
            'id' => "{$settingId}Help"
        ], h($setting['description']));
    }
    $error = '';
    if (!empty($setting['error'])) {
        $textColor = '';
        $textColor = "text-{$appView->get('variantFromSeverity')[$setting['severity']]}";
        $error = $appView->Bootstrap->genNode('div', [
            'class' => ['d-block', 'invalid-feedback', $textColor],
        ], h($setting['errorMessage']));
    }
    if (empty($setting['type'])) {
        $setting['type'] = 'string';
    }
    if ($setting['type'] == 'string') {
        $input = genInputString($settingId, $setting, $appView);
    } elseif ($setting['type'] == 'boolean') {
        $input = genInputCheckbox($settingId, $setting, $appView);
        $description = '';
    } elseif ($setting['type'] == 'integer') {
        $input = genInputInteger($settingId, $setting, $appView);
    } elseif ($setting['type'] == 'select') {
        $input = genInputSelect($settingId, $setting, $appView);
    } elseif ($setting['type'] == 'multi-select') {
        $input = genInputMultiSelect($settingId, $setting, $appView);
    } else {
        $input = genInputString($settingId, $setting, $appView);
    }
    $container = $appView->Bootstrap->genNode('div', [
        'class' => ['form-group', 'mb-2']
    ], implode('', [$label, $input, $description, $error]));
    return $container;
}

function genInputString($settingId, $setting, $appView)
{
    return $appView->Bootstrap->genNode('input', [
        'class' => [
            'form-control',
            "xxx-{$appView->get('variantFromSeverity')[$setting['severity']]} yyy-{$setting['severity']}",
            (!empty($setting['error']) ? 'is-invalid' : ''),
            (!empty($setting['error']) ? "border-{$appView->get('variantFromSeverity')[$setting['severity']]}" : ''),
            (!empty($setting['error']) && $setting['severity'] == 'warning' ? 'warning' : ''),
        ],
        'type' => 'text',
        'id' => $settingId,
        'value' => isset($setting['value']) ? $setting['value'] : "",
        'placeholder' => $setting['default'] ?? '',
        'aria-describedby' => "{$settingId}Help"
    ]);
}
function genInputCheckbox($settingId, $setting, $appView)
{
    $switch = $appView->Bootstrap->genNode('input', [
        'class' => [
            'custom-control-input'
        ],
        'type' => 'checkbox',
        'value' => !empty($setting['value']) ? 1 : 0,
        'checked' => !empty($setting['value']) ? 'checked' : '',
        'id' => $settingId,
    ]);
    $label = $appView->Bootstrap->genNode('label', [
        'class' => [
            'custom-control-label'
        ],
        'for' => $settingId,
    ], h($setting['description']));
    $container = $appView->Bootstrap->genNode('div', [
        'class' => [
            'custom-control',
            'custom-switch',
        ],
    ], implode('', [$switch, $label]));
    return $container;
}
function genInputInteger($settingId, $setting, $appView)
{
    return $appView->Bootstrap->genNode('input', [
        'class' => [
            'form-control'
        ],
        'type' => 'number',
        'min' => '0',
        'step' => 1,
        'id' => $settingId,
        'aria-describedby' => "{$settingId}Help"
    ]);
}
function genInputSelect($settingId, $setting, $appView)
{
}
function genInputMultiSelect($settingId, $setting, $appView)
{
}

function genScrollspyNav($nav1)
{
    $nav =  '<nav id="navbar-scrollspy-setting" class="navbar">';
    $nav .=     '<nav class="nav nav-pills flex-column">';
    foreach ($nav1 as $group => $sections) {
        $nav .= sprintf('<a class="nav-link main-group text-reset p-1" href="#%s">%s</a>', sprintf('sp-%s', h($group)), h($group));
        $nav .= sprintf('<nav class="nav nav-pills sub-group collapse flex-column" data-maingroup="%s">', sprintf('sp-%s', h($group)));
        foreach ($sections as $section) {
            $nav .= sprintf('<a class="nav-link nav-link-group text-reset ml-3 my-1 p-1" href="#%s">%s</a>', sprintf('sp-%s-%s', h($group), h($section)), h($section));
        }
        $nav .= '</nav>';
    }
    $nav .=     '</nav>';
    $nav .= '</nav>';
    return $nav;
}

function isLeaf($setting)
{
    return !empty($setting['name']) && !empty($setting['type']);
}

?>


<script>
    const settingsFlattened = <?= json_encode($settingsFlattened) ?>;
    let selectData = []
    for (const settingName in settingsFlattened) {
        if (Object.hasOwnProperty.call(settingsFlattened, settingName)) {
            const setting = settingsFlattened[settingName];
            const selectID = settingName.replaceAll('.', '_')
            selectData.push({
                id: selectID,
                text: setting.name,
                setting: setting
            })
        }
    }
    $(document).ready(function() {
        $('[data-spy="scroll"]').on('activate.bs.scrollspy', function(evt, {relatedTarget}) {
            const $associatedLink = $(`#navbar-scrollspy-setting nav.nav-pills .nav-link[href="${relatedTarget}"]`)
            let $associatedNav
            if ($associatedLink.hasClass('main-group')) {
                $associatedNav = $associatedLink.next()
            } else {
                $associatedNav = $associatedLink.parent()
            }
            const $allNavs = $('#navbar-scrollspy-setting nav.nav-pills.sub-group')
            $allNavs.removeClass('group-active').hide()
            $associatedNav.addClass('group-active').show()
        })

        $('.settings-tabs a[data-toggle="tab"]').on('shown.bs.tab', function (event) {
            $('[data-spy="scroll"]').trigger('scroll.bs.scrollspy')
        })

        $("#search-settings").select2({
            data: selectData,
            placeholder: '<?= __('Search setting by typing here...') ?>',
            templateResult: formatSettingSearchResult,
            templateSelection: formatSettingSearchSelection,
        })
            .on('select2:select', function (e) {
                const selected = e.params.data
                const settingPath = selected.setting['setting-path']
                const settingPathTokenized = settingPath.split('.')
                const tabName = settingPathTokenized[0]
                const IDtoFocus = 'sp-' + settingPathTokenized.slice(1).join('-')
                const $navController = $('.settings-tabs').find('a.nav-link').filter(function() {
                    return $(this).text() == tabName
                })
                if ($navController.length == 1) {
                    $toFocus = $(`#${IDtoFocus}`).parent()
                    if ($navController.hasClass('active')) {
                        $toFocus[0].scrollIntoView()
                        $toFocus.find(`input#${selected.id}`).focus()
                    } else {
                        $navController.on('shown.bs.tab.after-selection', () => {
                            $toFocus[0].scrollIntoView()
                            $toFocus.find(`input#${selected.id}`).focus()
                            $navController.off('shown.bs.tab.after-selection')
                        }).tab('show')
                    }
                }
                $("#search-settings").val(null).trigger('change.select2');
            })
    })

    function formatSettingSearchResult(state) {
        if (!state.id) {
            return state.text;
        }
        const $state = $('<div/>').append(
            $('<div/>').addClass('d-flex justify-content-between')
                .append(
                    $('<span/>').addClass('font-weight-bold').text(state.text),
                    $('<span/>').addClass('font-weight-light').text(state.setting['setting-path'].replaceAll('.', ' ▸ '))
                ),
            $('<div/>').addClass('font-italic font-weight-light ml-3').text(state.setting['description'])
        )
        return $state
    }
    
    function formatSettingSearchSelection(state) {
        return state.text
    }

</script>

<style>
    #navbar-scrollspy-setting nav.nav-pills .nav-link {
        background-color: unset !important;
        color: black;
        display: block;
    }

    #navbar-scrollspy-setting nav.nav-pills .nav-link:not(.main-group).active {
        color: #007bff !important;
        font-weight: bold;
    }

    #navbar-scrollspy-setting nav.nav-pills .nav-link.main-group:before {
        margin-right: 0.25em;
        font-family: 'Font Awesome 5 Free';
        font-weight: 900;
        -webkit-font-smoothing: antialiased;
        display: inline-block;
        font-style: normal;
        font-variant: normal;
        text-rendering: auto;
        line-height: 1;
    }

    #navbar-scrollspy-setting nav.nav-pills .nav-link.main-group.active:before {
        content: "\f0d7";
    }

    #navbar-scrollspy-setting nav.nav-pills .nav-link.main-group:before {
        content: "\f0da";
    }

    .select2-container {
        max-width: 100%;
        min-width: 100%;
    }
</style>