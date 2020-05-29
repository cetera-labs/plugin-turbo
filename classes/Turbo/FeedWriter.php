<?php

namespace Turbo;

class FeedWriter
{
    private $channels = array();  // Collection of channel elements
    private $items = array();  // Collection of items as object of FeedItem class.
    private $data = array();  // Store some other version wise data
    private $CDATAEncoding = array();  // The tag names which have to encoded as CDATA

    private $version = null;

    const Turbo = 'Turbo';
    const AMP = 'AMP';
    const Telegram = 'Telegram';


    function __construct($version = self::Turbo)
    {
        $this->version = $version;

        // Setting default value for assential channel elements
        $this->channels['link'] = '';
        $this->channels['title'] = $version . ' Feed';

        //Tag names to encode in CDATA
        $this->CDATAEncoding = array('description', 'content:encoded', 'summary', 'title', 'yandex:full-text');
    }

    // Start # public functions ---------------------------------------------

    /**
     * Set multiple channel elements from an array. Array elements
     * should be 'channelName' => 'channelContent' format.
     *
     * @access   public
     * @param    array   array of channels
     * @return   void
     */
    public function setChannelElementsFromArray($elementArray)
    {
        if (!is_array($elementArray)) return;
        foreach ($elementArray as $elementName => $content) {
            $this->setChannelElement($elementName, $content);
        }
    }

    /**
     * Set a channel element
     * @access   public
     * @param    srting  name of the channel tag
     * @param    string  content of the channel tag
     * @return   void
     */
    public function setChannelElement($elementName, $content)
    {
        $this->channels[$elementName] = $content;
    }

    /**
     * Genarate the actual Turbo file
     *
     * @access   public
     * @return   void
     */
    public function genarateFeed($yandex = false, $m = "")
    {
        if ($this->version == self::AMP || $this->version == self::Telegram) {
            header("Content-type: text/html");
        }

        else {
            header("Content-type: text/xml");
        }

        $this->printHead($m);
        $this->printChannels();
        $this->printItems();
        $this->printTale();

    }

    /**
     * Prints the xml and rss namespace
     *
     * @access   private
     * @return   void
     */
    private function printHead($m = "")
    {
        if ($this->version != self::AMP && $this->version != self::Telegram) {
            $out = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
        }

        if ($this->version == self::Turbo) {
            $out .= '<rss xmlns:turbo="http://turbo.yandex.ru" version="2.0">' . PHP_EOL;
        } else if ($this->version == self::AMP) {
            $out .= '<!doctype html>' . PHP_EOL . '<html amp>' . PHP_EOL . '<head>' . PHP_EOL . '<meta charset="utf-8">' . PHP_EOL .
                '<script async src="https://cdn.ampproject.org/v0.js"></script>' . PHP_EOL .
                '<link rel="canonical" href="'.$m["fullPath"].'">' . PHP_EOL .
                '<meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">' . PHP_EOL .
                '<style amp-boilerplate>body{-webkit-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-moz-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-ms-animation:-amp-start 8s steps(1,end) 0s 1 normal both;animation:-amp-start 8s steps(1,end) 0s 1 normal both}@-webkit-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-moz-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-ms-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-o-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}</style><noscript><style amp-boilerplate>body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}</style></noscript>'
                . PHP_EOL . '<style amp-custom>body{padding-left: 10px; padding-right: 10px;} img{display:none;}</style>' . PHP_EOL . '<title>' . $m["name"] . '</title>' .
                '</head>' . PHP_EOL . '<body>' . PHP_EOL;

        } else if ($this->version == self::Telegram) {
            $out .= '<!doctype html>' . PHP_EOL . '<html>' . PHP_EOL . '<head>' . PHP_EOL . '<meta charset="utf-8">' . PHP_EOL .
                '<link rel="canonical" href="'.$_SERVER[HTTP_X_FORWARDED_PROTO] . "://" . $_SERVER[HTTP_HOST].$_SERVER['REQUEST_URI'].'">' . PHP_EOL .
                '<meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">' . PHP_EOL .
                '<title>' . $m["name"] . '</title>' . '</head>' . PHP_EOL . '<body>' . PHP_EOL;

        }
        echo $out;
    }

    /**
     * @desc     Print channels
     * @access   private
     * @return   void
     */
    private function printChannels()
    {
        //Start channel tag
        if ($this->version == self::Turbo) {
            echo '<channel>' . PHP_EOL;
        }
    }


    // Wrapper functions -------------------------------------------------------------------

    /**
     * Creates a single node as xml format
     *
     * @access   private
     * @param    srting  name of the tag
     * @param    mixed   tag value as string or array of nested tags in 'tagName' => 'tagValue' format
     * @param    array   Attributes(if any) in 'attrName' => 'attrValue' format
     * @return   string  formatted xml tag
     */
    private function makeNode($tagName, $tagContent, $attributes = null)
    {
        $nodeText = '';
        $attrText = '';

        if (is_array($attributes)) {
            foreach ($attributes as $key => $value) {
                $attrText .= " $key=\"$value\" ";
            }
        }

        if (is_array($tagContent) && $this->version == RSS1) {
            $attrText = ' rdf:parseType="Resource"';
        }


        $attrText .= (in_array($tagName, $this->CDATAEncoding) && $this->version == ATOM) ? ' type="html" ' : '';


        if (in_array($tagName, $this->CDATAEncoding)) {

            if ($this->version == self::Turbo) {
                $str = "<![CDATA[";
            }

            else {
                $str = "<{$tagName}{$attrText}><![CDATA[";
            }
        }

        elseif ($this->version == self::AMP || $this->version == self::Telegram) {
            $str = "{$tagName}{$attrText}";
        }

        else {
            $str = "<{$tagName}{$attrText}>";
        }

        $nodeText .= $str;

        if (is_array($tagContent)) {
            foreach ($tagContent as $key => $value) {
                $nodeText .= $this->makeNode($key, $value);
            }
        }

        else {
            $nodeText .= $tagContent;
        }


        if (in_array($tagName, $this->CDATAEncoding)) {

            if ($this->version == self::Turbo) {
                $str = "]]>";
            }

            else {
                $str = "]]></$tagName>";
            }
        }

        elseif ($this->version == self::AMP || $this->version == self::Telegram) {
            $str = "$tagName";
        }

        else {
            $str = "</$tagName>";
        }

        $nodeText .= $str;

        return $nodeText . PHP_EOL;
    }



    /**
     * Prints formatted feed items
     *
     * @access   private
     * @return   void
     */
    private function printItems()
    {
        foreach ($this->items as $item) {
            $thisItems = $item->getElements();


            echo $this->startItem($thisItems['link']['content']);

            foreach ($thisItems as $feedItem) {
                echo $this->makeNode($feedItem['name'], $feedItem['content'], $feedItem['attributes']);
            }
            echo $this->endItem();
        }
    }

    /**
     * Make the starting tag of channels
     *
     * @access   private
     * @param    srting  The vale of about tag which is used for only Turbo
     * @return   void
     */
    private function startItem($about = false)
    {   if ($this->version == self::Turbo) {
            echo '<item turbo="true">' . PHP_EOL;
        }
    }

    /**
     * Closes feed item tag
     *
     * @access   private
     * @return   void
     */
    private function endItem()
    {
        if ($this->version == self::Turbo) {
            echo '</item>' . PHP_EOL;
        }
    }

    /**
     * Closes the open tags at the end of file
     *
     * @access   private
     * @return   void
     */
    private function printTale()
    {
        if ($this->version == self::Turbo) {
            echo '</channel>' . PHP_EOL . '</rss>';
        } else if ($this->version == self::AMP || $this->version == self::Telegram) {
            echo '</body>' . PHP_EOL . '</html>';
        }

    }
    // End # public functions ----------------------------------------------

    // Start # private functions ----------------------------------------------

    /**
     * Create a new FeedItem.
     *
     * @access   public
     * @return   object  instance of FeedItem class
     */
    public function createNewItem()
    {
        $Item = new FeedItem($this->version);
        return $Item;
    }

    /**
     * Add a FeedItem to the main class
     *
     * @access   public
     * @param    object  instance of FeedItem class
     * @return   void
     */
    public function addItem($feedItem)
    {
        $this->items[] = $feedItem;
    }

    /**
     * Set the 'link' channel element
     *
     * @access   public
     * @param    srting  value of 'link' channel tag
     * @return   void
     */
    public function setLink($link)
    {
        $this->setChannelElement('link', $link);
    }

    /**
     * Set the 'title' channel element
     *
     * @access   public
     * @param    srting  value of 'title' channel tag
     * @return   void
     */
    public function setTitle($title)
    {
        $this->setChannelElement('title', $title);
    }

    /**
     * Set the 'description' channel element
     *
     * @access   public
     * @param    srting  value of 'description' channel tag
     * @return   void
     */
    public function setDescription($desciption)
    {
        $this->setChannelElement('description', $desciption);
    }



    /**
     * Set the 'image' channel element
     *
     * @access   public
     * @param    srting  title of image
     * @param    srting  link url of the imahe
     * @param    srting  path url of the image
     * @return   void
     */
    public function setImage($title, $link, $url)
    {
        $this->setChannelElement('image', array('title' => $title, 'link' => $link, 'url' => $url));
    }

    /**
     * Set the 'about' channel element. Only for Turbo
     *
     * @access   public
     * @param    srting  value of 'about' channel tag
     * @return   void
     */
    public function setChannelAbout($url)
    {
        $this->data['ChannelAbout'] = $url;
    }


    // End # private functions ----------------------------------------------

} // end of class FeedWriter

// autoload classes

