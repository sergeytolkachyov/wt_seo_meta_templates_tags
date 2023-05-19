<?php
/**
 * @package     WT SEO Meta templates
 * @subpackage  WT SEO Meta templates - Tags
 * @version     1.0.0
 * @Author      Sergey Tolkachyov, https://web-tolk.ru
 * @copyright   Copyright (C) 2023 Sergey Tolkachyov
 * @license     GNU/GPL http://www.gnu.org/licenses/gpl-2.0.html
 * @since       1.0.0
 */


namespace Joomla\Plugin\System\Wt_seo_meta_templates_tags\Extension;

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Profiler\Profiler;
use Joomla\Filter\OutputFilter;
use Joomla\Registry\Registry;

class Wt_seo_meta_templates_tags extends CMSPlugin
{
    /**
     * Load the language file on instantiation.
     *
     * @var  $autoloadLanguage  boolean
     *
     * @since  3.9.0
     */
    protected $autoloadLanguage = true;

    /**
     * @var $show_debug bool Show debug flag
     * @since 1.0.0
     */
    protected $show_debug = false;

    public function __construct(&$subject, $config = [])
    {
        parent::__construct($subject, $config);

        if (PluginHelper::isEnabled('system', 'wt_seo_meta_templates')) {
            $main_plugin = PluginHelper::getPlugin('system', 'wt_seo_meta_templates');
            $main_plugin_params = new Registry($main_plugin->params);
            $this->show_debug = $main_plugin_params->get('show_debug');
        }

    }

    public function onWt_seo_meta_templatesAddVariables()
    {

        !JDEBUG ?: Profiler::getInstance('Application')->mark('<strong>plg WT SEO Meta templates - com_tags provider plugin</strong>: start');
        $app = Factory::getApplication();
        $option = $app->getInput()->get('option');

        if ($option != 'com_tags') {
            return;
        }

        $variables = array();
        // Titles and meta-descriptiptions array for export to main wt seo meta templates plugin
        $seo_meta_template = array();
        // Short codes for com_tags category view
        if ($app->getInput()->get('view') == 'tags') {

            /**
             * Добавляем или нет суффикс к title и meta-description страницы
             * для страниц пагинации.
             */

            //$limitstart - признак страницы пагинации, текущая страница пагинации
            $limitstart = $app->getInput()->get('limitstart', 0, 'uint');
            if (isset($limitstart) && (int)$limitstart > 0
                && $this->params->get('enable_page_title_and_metadesc_pagination_suffix') == 1) {

                /** @var \Joomla\Component\Tags\Site\Model\TagsModel $tags_model */
                $tags_model = $app->bootComponent('com_tags')->getMVCFactory()
                    ->createModel('Tags', 'Site', ['ignore_request' => false]);

                $tags_model->getItems();
                $title = Factory::getApplication()->getDocument()->getHeadData()['title'];
                $metadesc = Factory::getApplication()->getDocument()->getHeadData()['description'];
                if ($this->show_debug == true) {
                    $this->prepareDebugInfo('', '<p><strong>com_tags area</strong>: tags</p>');
                    $this->prepareDebugInfo('', '<p><strong>com_tags Title</strong>: ' . $title . '</p>');
                    $this->prepareDebugInfo('', '<p><strong>com_tags Meta desc:</strong> ' . $metadesc . '</p>');
                }
                $pagination = $tags_model->getPagination();
                $current_pagination_page_num = $pagination->pagesCurrent;


                // Тексты суффиксов из параметров плагина
                if (!empty($this->params->get('page_title_pagination_suffix_text'))) {
                    // Суффиксы для страниц пагинации - "- страница NNN".
                    $pagination_suffix_title = sprintf(Text::_($this->params->get('page_title_pagination_suffix_text')), $current_pagination_page_num);

                    //Если шаблоны отключены - просто добавляем суффиксы в пагинацию
                    $seo_meta_template['title'] = $title . ' ' . $pagination_suffix_title;
                }

                if (!empty($this->params->get('page_metadesc_pagination_suffix_text'))) {

                    $pagination_suffix_metadesc = sprintf(Text::_($this->params->get('page_metadesc_pagination_suffix_text')), $current_pagination_page_num);

                    //Если шаблоны отключены - просто добавляем суффиксы в пагинацию
                    $seo_meta_template['description'] = $metadesc . ' ' . $pagination_suffix_metadesc;
                }
            }
        } elseif ($app->getInput()->get('view') == 'tag') {

            !JDEBUG ?: Profiler::getInstance('Application')->mark('<strong>plg WT SEO Meta templates - com_tags provider plugin</strong>: Before load article');
            $this->prepareDebugInfo('', '<p><strong>com_tags area</strong> tag</p>');
            $tag_ids_array = $app->getInput()->get('id', [], 'array');

            $tags_titles = [];
            $parent_tag_title = '';

            /** @var \Joomla\Component\Tags\Site\Model\TagModel $tag_model */
            $tag_model = $app->bootComponent('com_tags')->getMVCFactory()
                ->createModel('Tag', 'Site', ['ignore_request' => false]);

            $tag_array = [];
            foreach ($tag_ids_array as $tag_id_string) {

                if (strpos($tag_id_string, ':')) {
                    $tag_id_array = explode(':', $tag_id_string);
                    $tag_array[] = $tag_id_array[0];
                } else {
                    $tag_array[] = $tag_id_string;
                }

            }
            $tags = $tag_model->getItem(implode(',', $tag_array));
            $tag_title = $tags[0]->title;

            $tag_description = $tags[0]->description;

            if (count($tags) > 1) {
                foreach ($tags as $tag) {
                    $tags_titles[] = $tag->title;
                }
            }

            if ((int)$tags[0]->parent_id > 1) {
                /** @var \Joomla\Component\Tags\Site\Model\TagModel $parent_tag_model */
                $parent_tag_model = $app->bootComponent('com_tags')->getMVCFactory()
                    ->createModel('Tag', 'Site', ['ignore_request' => true]);
                $parent_tag = $parent_tag_model->getItem((int)$tag[0]->parent_id);
                $parent_tag_title = $parent_tag[0]->title;
            }

            !JDEBUG ?: Profiler::getInstance('Application')->mark('<strong>plg WT SEO Meta templates - com_tags provider plugin</strong>: After load article');
            $this->prepareDebugInfo('', '<p><strong>com_tags Title</strong>: ' . $tag_title . '</p>');
            $this->prepareDebugInfo('', '<p><strong>com_tags Meta desc:</strong> ' . $tags[0]->metadesc . '</p>');
            /**
             * com_tags tag variables for short codes
             */
            // Tag title
            $variables[] = [
                'variable' => 'CT_TAG_TITLE',
                'value' => $tag_title,
            ];
            // All existing tags titles
            $variables[] = [
                'variable' => 'CT_TAGS_TITLES',
                'value' => implode(', ', $tags_titles),
            ];

            // Paren tag title
            $variables[] = [
                'variable' => 'CT_PARENT_TAG_TITLE',
                'value' => $parent_tag_title,
            ];


            // Tag intro text
            if (!empty($tag_description)) {
                (int)$intro_text_max_lenght = $this->params->get('tag_description_text_max_chars', 200);

                $tag_description = HTMLHelper::_('content.prepare',$tag_description, '', 'com_content.article');
                $tag_description = trim(strip_tags(html_entity_decode($tag_description, ENT_QUOTES, 'UTF-8')));
                $tag_description = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '   '), ' ', $tag_description);

                if ($intro_text_max_lenght > 3) {
                    $intro_text_max_lenght = $intro_text_max_lenght - 3; // For '...' in the end of string
                }
                $tag_description = mb_substr($tag_description, 0, $intro_text_max_lenght, 'utf-8');
                $tag_description = $tag_description . '...';

            } else {
                $tag_description = '';
            }

            $variables[] = [
                'variable' => 'CT_TAG_INTRO',
                'value' => $tag_description,
            ];

            if ($this->params->get('global_tag_title_replace') == 1) {

                //Переписываем все глобально
                if ($this->show_debug == true) {
                    $this->prepareDebugInfo('', '<p>' . Text::_('PLG_WT_SEO_META_TEMPLATES_TAGS_DEBUG_GLOBAL_TAG_TITLE_REPLACE') . '</p>');
                }

                // Глобальная сео-формула для всех материалов
                $title_template = $this->params->get('tag_title_template');

                if (!empty($title_template)) {
                    $seo_meta_template['title'] = $title_template;
                }
            }

            /**
             * Если включена глобальная перезапись description тега. Все по формуле.
             */

            if ($this->params->get('global_tag_meta_description_replace') == 1) {

                /**
                 * Если переписываем только пустые. Там, где пустое
                 * $tag->metadesc
                 */

                if ($this->params->get('global_tag_meta_description_replace_only_empty') == 1) {
                    if ($this->show_debug == true) {
                        $this->prepareDebugInfo('', '<p>' . Text::_('PLG_WT_SEO_META_TEMPLATES_TAGS_DEBUG_GLOBAL_TAG_META_DESCRIPTION_REPLACE_ONLY_EMPTY') . '</p>');
                    }
                    if (empty($tags[0]->metadesc) == true) {
                        if ($this->show_debug == true) {
                            $this->prepareDebugInfo('', '<p>' . Text::_('PLG_WT_SEO_META_TEMPLATES_TAGS_DEBUG_EMPTY_TAG_META_DESCRIPTION_FOUND') . '</p>');
                        }

                        // Глобальная сео-формула для всех материалов
                        $description_template = $this->params->get('tag_meta_description_template');

                        if (!empty($description_template)) {
                            $seo_meta_template['description'] = $description_template;
                        }
                    }
                } else {
                    //Переписываем все глобально
                    if ($this->show_debug == true) {
                        $this->prepareDebugInfo('', '<p>' . Text::_('PLG_WT_SEO_META_TEMPLATES_TAGS_DEBUG_GLOBAL_TAG_META_DESCRIPTION_REPLACE') . '</p>');
                    }

                    // Глобальная сео-формула для всех материалов
                    $description_template = $this->params->get('tag_meta_description_template');

                    if (!empty($description_template)) {
                        $seo_meta_template['description'] = $description_template;
                    }
                }
            }


            /**
             * Добавляем или нет суффикс к title и meta-description страницы
             * для страниц пагинации.
             */

            //$limitstart - признак страницы пагинации, текущая страница пагинации
            $limitstart = $app->getInput()->get('limitstart', 0, 'uint');
            if (isset($limitstart) && (int)$limitstart > 0) {

                if ($this->params->get('enable_page_title_and_metadesc_pagination_suffix') == 1) {

                    $pagination = $tag_model->getPagination();
                    $current_pagination_page_num = $pagination->pagesCurrent;

                    // Тексты суффиксов из параметров плагина

                    if (!empty($this->params->get('page_title_pagination_suffix_text'))) {
                        // Суффиксы для страниц пагинации - "- страница NNN".
                        $pagination_suffix_title = sprintf(Text::_($this->params->get('page_title_pagination_suffix_text')), $current_pagination_page_num);

                        if (!empty($seo_meta_template['title']) && !empty($pagination_suffix_title)) {
                            $seo_meta_template['title'] = $seo_meta_template['title'] . ' ' . $pagination_suffix_title;
                        } elseif (!empty($pagination_suffix_title)) {
                            //Если шаблоны отключены - просто добавляем суффиксы в пагинацию
                            $seo_meta_template['title'] = $tags[0]->title . ' ' . $pagination_suffix_title;
                        }

                    }

                    if (!empty($this->params->get('page_metadesc_pagination_suffix_text'))) {

                        $pagination_suffix_metadesc = sprintf(Text::_($this->params->get('page_metadesc_pagination_suffix_text')), $current_pagination_page_num);

                        // Суффиксы для страниц пагинации - "- страница NNN".
                        if (!empty($seo_meta_template['description']) && !empty($pagination_suffix_metadesc)) {
                            $seo_meta_template['description'] = $seo_meta_template['description'] . ' ' . $pagination_suffix_metadesc;
                        } elseif (!empty($pagination_suffix_metadesc)) {
                            //Если шаблоны отключены - просто добавляем суффиксы в пагинацию
                            $seo_meta_template['description'] = $tags[0]->metadesc . ' ' . $pagination_suffix_metadesc;
                        }
                    }
                }

            }//pagination

        }
        $data = array(
            'variables' => $variables,
            'seo_tags_templates' => $seo_meta_template,
        );


        $this->prepareDebugInfo('SEO variables', $data);

        !JDEBUG ?: Profiler::getInstance('Application')->mark('<strong>plg WT SEO Meta templates - com_tags provider plugin</strong>: Before return data. End.');

        return $data;
    }

    /**
     * Prepare html output for debug info from main function
     *
     * @param $debug_section_header string
     * @param $debug_data           string|array
     *
     * @return void
     * @since 1.0.0
     */
    private function prepareDebugInfo($debug_section_header, $debug_data): void
    {

        if ($this->show_debug == true) {
            $session = Factory::getApplication()->getSession();
            $debug_output = $session->get("wtseometatemplatesdebugoutput");
            if (!empty($debug_section_header)) {
                $debug_output .= "<details style='border:1px solid #0FA2E6; margin-bottom:5px;'>";
                $debug_output .= "<summary style='background-color:#384148; color:#fff; padding:10px;'>" . $debug_section_header . "</summary>";
            }

            if (is_array($debug_data) || is_object($debug_data)) {
                $debug_data = print_r($debug_data, true);
                $debug_output .= "<pre style='background-color: #eee; padding:10px;'>";
            }

            $debug_output .= $debug_data;
            if (is_array($debug_data) || is_object($debug_data)) {
                $debug_output .= "</pre>";
            }
            if (!empty($debug_section_header)) {
                $debug_output .= "</details>";
            }
            $session->set("wtseometatemplatesdebugoutput", $debug_output);
        }
    }
}//plgSystemWt_seo_meta_templates_content