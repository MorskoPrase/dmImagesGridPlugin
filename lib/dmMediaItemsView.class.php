<?php

/**
 * Description of dmMediaItemsView
 *
 * @author TheCelavi
 */
class dmMediaItemsView extends dmWidgetPluginView {

    public function configure() {
        parent::configure();
    }

    public function filterViewVars(array $vars = array()) {
        $vars = parent::filterViewVars($vars);
        if (!isset($vars['media_item'])) {
            $vars['media_items'] = null;
            return $vars;
        }
        $media_items = json_decode($vars['media_item'], true);
        unset($vars['media_item']);
        $vars['media_items'] = $this->prepareImages($media_items, $vars['thumbnail_width'], $vars['thumbnail_height'], $vars['thumbnail_image_resize_method'], $vars['thumbnail_image_resize_quality']);
        
        // MAX WIDTH/HEIGHT => The solution
        switch ($vars['container_style']){
            case 'elastic': $vars['container_style'] = ""; break;
            case 'fixed_width': $vars['container_style'] = sprintf("overflow: %s; width: %spx;" , $vars['overflow'], $vars['container_width']); break;
            case 'fixed_height': $vars['container_style'] = sprintf("overflow: %s; height: %spx;" , $vars['overflow'], $vars['container_height']); break;
            case 'fixed': $vars['container_style'] = sprintf("overflow: %s; width: %spx; height: %spx;" , $vars['overflow'], $vars['container_width'], $vars['container_height']); break;
        }
        
        unset ($vars['overflow']);
        unset ($vars['container_width']);
        unset ($vars['container_height']);
        
        $vars['show_title'] = ($vars['show_title'] == 'true') ? true : false;
        return $vars;
    }

    protected function doRender() {
        if ($this->isCachable() && $cache = $this->getCache()) {
            return $cache;
        }
        $vars = $this->getViewVars();
        if (is_null($vars['media_items']))  return $this->renderDefault();
        $templates = sfConfig::get('dm_dmImagesGridPlugin_templates');
        if (isset($templates[$vars['templates']]['css']) && sizeof($templates[$vars['templates']]['css']) > 0) $this->addStylesheet($templates[$vars['templates']]['css']);
        if (isset($templates[$vars['templates']]['js']) && sizeof($templates[$vars['templates']]['js']) > 0) $this->addJavascript($templates[$vars['templates']]['js']);                 
        $html = $this->getHelper()->renderPartial('dmImagesGrid', $vars['templates'], array(
            'media_items' => $vars['media_items'],
            'show_title' => $vars['show_title'],
            'width' => $vars['thumbnail_width'],
            'height' => $vars['thumbnail_height'],
            'display_style' => $vars['thumbnail_display_style'],
            'display_per_row' => $vars['thumbnail_display_per_row'],
            'css_class_images'=>$vars['css_class_images'],
            'css_class_links'=>$vars['css_class_links'],
            'container_style' => $vars['container_style']
        ));
        if ($this->isCachable()) {
            $this->setCache($html);
        }
        return $html;
    }

    protected function prepareImages($media_items, $width, $height, $resize_method, $resize_quality) {     
        // Speed up fetch images
        $mediaIds = array();
        foreach ($media_items as $tmp) $mediaIds[$tmp['media_id']] = $tmp['media_id'];
        $mediaObjects = $this->getMedia($mediaIds);
        foreach ($mediaObjects as $obj) $mediaIds[$obj->getId()] = $obj;
        // End speed up fetch images
        
        $images = array();
        foreach ($media_items as $index => $media_item) {
            $image = array(
                'thumbnail'         =>          $mediaIds[$media_item["media_id"]],
                'width'             =>          $width,
                'height'            =>          $height,
                'title'             =>          $media_item['media_title'],
                'resize_method'     =>          $resize_method,
                'resize_quality'    =>          $resize_quality,
                'image_config'      =>          $media_item['media_config'],
                'link'              =>          $media_item['link'],
                'link_config'       =>          $media_item['link_config']
            );
            $image = $this->resolveLink($this->resolveImage($image));            
            $images[] = $image;
        }        
        return $images;
    }
    protected function getMedia($mediaIds) {
        return dmDb::query('DmMedia m')
                        ->leftJoin('m.Folder f')
                        ->whereIn('m.id', $mediaIds)
                        ->execute();
    }
    
    // TODO Parse YAML config
    protected function resolveImage($image) {
        $helper = $this->getHelper();
        $image['thumbnail'] = $helper->media($image['thumbnail'])
                ->size($image['width'], $image['height'])
                ->method($image['resize_method'])
                ->quality($image['resize_quality'])
                ->getSrc();
        return $image;
    }
    // TODO Parse YAML config
    protected function resolveLink($image) {
        return $image;
    }
    
}
