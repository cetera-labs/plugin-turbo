<?php

namespace Turbo;


class FeedItem
{
    private $elements = array();    //Collection of feed elements
    private $version;


    function __construct($version = FeedWriter::Turbo)
    {
        $this->version = $version;
    }

    /**
     * Set multiple feed elements from an array.
     * Elements which have attributes cannot be added by this method
     *
     * @access   public
     * @param    array   array of elements in 'tagName' => 'tagContent' format.
     * @return   void
     */
    public function addElementArray($elementArray)
    {
        if (!is_array($elementArray)) return;
        foreach ($elementArray as $elementName => $content) {
            $this->addElement($elementName, $content);
        }
    }

    /**
     * Add an element to elements array
     *
     * @access   public
     * @param    srting  The tag name of an element
     * @param    srting  The content of tag
     * @param    array   Attributes(if any) in 'attrName' => 'attrValue' format
     * @return   void
     */
    public function addElement($elementName, $content, $attributes = null)
    {
        $this->elements[$elementName]['name'] = $elementName;
        $this->elements[$elementName]['content'] = $content;
        $this->elements[$elementName]['attributes'] = $attributes;
    }

    /**
     * Return the collection of elements in this feed item
     *
     * @access   public
     * @return   array
     */
    public function getElements()
    {
        return $this->elements;
    }

    // Wrapper functions ------------------------------------------------------

    /**
     * Set the 'dscription' element of feed item
     *
     * @access   public
     * @param    string  The content of 'description' element
     * @return   void
     */
    public function setDescription($description)
    {
        if ($this->version != FeedWriter::Turbo) {
            $this->addElement($tag, strip_tags($description));
        }
    }

    /**
     * @desc     Set the 'title' element of feed item
     * @access   public
     * @param    string  The content of 'title' element
     * @return   void
     */
    public function setTitle($title)
    {   if ($this->version != FeedWriter::Turbo) {
            $this->addElement('title', $title);
        }
    }

    /**
     * Set the 'date' element of feed item
     *
     * @access   public
     * @param    string  The content of 'date' element
     * @return   void
     */
    public function setDate($date)
    {
        if (!is_numeric($date)) {
            $date = strtotime($date);
        }

        if ($this->version == FeedWriter::Turbo) {
            $tag = 'pubDate';
            $value = date(DATE_RSS, $date);
        }

        $this->addElement($tag, $value);
    }

    /**
     * Set the 'link' element of feed item
     *
     * @access   public
     * @param    string  The content of 'link' element
     * @return   void
     */
    public function setLink($link)
    {
        if ($this->version == FeedWriter::Turbo) {
            $this->addElement('link', $link . '/');
        }
    }

    public function turboContent($turboContent) {
        $this->addElement('turbo:content', $turboContent);
    }

    public function ampContent($ampContent) {
        $this->addElement('', $ampContent);
    }


    /**
     * Set the 'encloser' element of feed item
     * For Turbo only
     *
     * @access   public
     * @param    string  The url attribute of encloser tag
     * @param    string  The length attribute of encloser tag
     * @param    string  The type attribute of encloser tag
     * @return   void
     */
    public function setEncloser($url, $length, $type)
    {
        $attributes = array('url' => $url, 'length' => $length, 'type' => $type);
        $this->addElement('enclosure', '', $attributes);
    }

} // end of class FeedItem
?>
