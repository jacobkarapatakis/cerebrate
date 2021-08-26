<?php

namespace App\View\Helper;

use Cake\View\Helper;
use Cake\Utility\Hash;

class TagHelper extends Helper
{
    public $helpers = [
        'Bootstrap',
        'TextColour',
        'FontAwesome',
        'Form',
        'Url',
        'Tags.Tag',
    ];

    protected $defaultConfig = [
        'default_colour' => '#924da6',
        'picker' => false,
        'editable' => false,
    ];

    public function control(array $options = [])
    {
        $field = 'tag_list';
        $values = !empty($options['allTags']) ? array_map(function($tag) {
            return [
                'text' => h($tag['text']),
                'value' => h($tag['text']),
                'data-colour' => h($tag['colour']),
            ];
        }, $options['allTags']) : [];
        $selectConfig = [
            'multiple' => true,
            // 'value' => $options['tags'],
            'class' => ['tag-input', 'd-none']
        ];
        return $this->Form->select($field, $values, $selectConfig);
    }

    protected function picker(array $options = [])
    {
        $html =  $this->Tag->control($options);
        $html .= $this->Bootstrap->button([
            'size' => 'sm',
            'icon' => 'plus',
            'variant' => 'secondary',
            'class' => ['badge'],
            'params' => [
                'onclick' => 'createTagPicker(this)',
            ]
        ]);
        return $html;
    }

    public function tags(array $options = [])
    {
        $this->_config = array_merge($this->defaultConfig, $options);
        $tags = !empty($options['tags']) ? $options['tags'] : [];
        $html = '<div class="tag-container-wrapper">';
        $html .= '<div class="tag-container my-1">';
        $html .= '<div class="tag-list d-inline-block">';
        foreach ($tags as $tag) {
            if (is_array($tag)) {
                $html .= $this->tag($tag);
            } else {
                $html .= $this->tag([
                    'name' => $tag
                ]);
            }
        }
        $html .= '</div>';
        
        if (!empty($this->getConfig('picker'))) {
            $html .= $this->picker($options);
        }
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    public function tag(array $tag, array $options = [])
    {
        if (empty($this->_config)) {
            $this->_config = array_merge($this->defaultConfig, $options);
        }
        $tag['colour'] = !empty($tag['colour']) ? $tag['colour'] : $this->getConfig('default_colour');
        $textColour = $this->TextColour->getTextColour(h($tag['colour']));

        if (!empty($this->getConfig('editable'))) {
            $deleteButton = $this->Bootstrap->button([
                'size' => 'sm',
                'icon' => 'times',
                'class' => ['ml-1', 'border-0', "text-${textColour}"],
                'variant' => 'text',
                'title' => __('Delete tag'),
                'params' => [
                    'onclick' => sprintf('deleteTag(\'%s\', \'%s\', this)',
                        $this->Url->build([
                            'controller' => $this->getView()->getName(),
                            'action' => 'untag',
                            $this->getView()->get('entity')['id']
                        ]),
                        h($tag['name'])
                    ),
                ],
            ]);
        } else {
            $deleteButton = '';
        }

        $html = $this->Bootstrap->genNode('span', [
            'class' => [
                'tag',
                'badge',
                'mx-1',
                'align-middle',
            ],
            'title' => h($tag['name']),
            'style' => sprintf('color:%s; background-color:%s', $textColour, h($tag['colour'])),
        ], h($tag['name']) . $deleteButton);
        return $html;
    }
}
