<?php
namespace mrfakename\GIF;
Class GIFGenerator {

    private $_defaultYPosition;
    private $_defaultXPosition;
    private $_defaultAngle;
    private $_defaultFont;
    private $_defaultFontColor;
    private $_defaultFontSize;
    private $_defaultDelay;
    private $_defaultRepeat;

    /**
     * Constructor of the GIFGenerator object which sets the default values
     *
     * @param array $kwargs default values to override
     */
    function __construct(array $kwargs = []) {

        // Set defaults
        $defaults = [
            "y-position" => 100,
            "x-position" => 100,
            "angle" => 0,
            "fonts" => __DIR__.'/fonts/RobotoMono-Bold.ttf',
            "fonts-color" => [255,255,255],
            "fonts-size" => 12,
            "delay" => 100,
            "repeat" => 0
        ];

        // Overwrite all the defaults with the arguments
        $args = array_merge($defaults,$kwargs);

        $this->_defaultYPosition = $args['y-position'];
        $this->_defaultXPosition = $args['x-position'];
        $this->_defaultAngle = $args['angle'];
        $this->_defaultFont = $args['fonts'];
        $this->_defaultFontColor = $args['fonts-color'];
        $this->_defaultFontSize = $args['fonts-size'];
        $this->_defaultDelay = $args['delay'];
        $this->_defaultRepeat = $args['repeat'];
    }

    /**
     * imagettftext with letter-spacing as extra feature
     *
     * @param  string  $image   	background image of the GIF
     * @param  integer $fontsize    fontsize of the text
     * @param  integer $angle   	rotation angle of the text
     * @param  integer $x       	x-position of the text inside the image
     * @param  integer $y       	y-position of the text inside the image
     * @param  string  $color   	text color
     * @param  integer $font    	font-family fo the text
     * @param  string  $text    	the actual text
     * @param  integer $spacing 	letter-spacing of the text
     * @return void
     */
    private function imagettftextSp($image, $fontsize, $angle, $x, $y, $color, $font, $text, $spacing = 0) {
        if ($spacing == 0) {
            $txt = imagettftext($image, $fontsize, $angle, $x, $y, $color, $font, $text);
        } else {
            $temp_x = $x;
            for ($i = 0; $i < strlen($text); $i++) {
                $txt = imagettftext($image, $fontsize, $angle, $temp_x, $y, $color, $font, $text[$i]);
                $temp_x += $spacing + ($txt[2] - $txt[0]);
            }
        }
    }

    /**
     * Generates the actual GIF image
     *
     * @param  array  	$array array with all image frames
     * @return resource        returns the actual GIF image
     */
    public function generate(array $array) {
        $frames = [];
        $frame_delay = [];

        foreach($array['frames'] as $frame) {

            $image = $this->_createImage($frame['image']);

            if(array_key_exists('text', $frame))
                foreach($frame['text'] as $key => $text) {

                    // Set defaults
                    $defaults = [
                        "angle" => $this->_defaultAngle,
                        "fonts" => $this->_defaultFont,
                        "fonts-color" => $this->_defaultFontColor,
                        "fonts-size" => $this->_defaultFontSize,
                        "y-position" => $this->_defaultYPosition,
                        "x-position" => $this->_defaultXPosition,
                        "text" => null,
                        "letter-spacing" => 0
                    ];

                    // Overwrite all the defaults with the arguments
                    $args = array_merge($defaults, $text);
                    $fontColor = is_array($args['fonts-color']) ? $args['fonts-color'] : $this->_hex2rgb($args['fonts-color']);
                    $text_color = imagecolorallocate($image, $fontColor[0], $fontColor[1], $fontColor[2]);

                    $this->imagettftextSp(
                        $image,
                        $args['fonts-size'],
                        $args['angle'],
                        $args['x-position'],
                        $args['y-position'],
                        $text_color,
                        $args['fonts'],
                        $args['text'],
                        $args['letter-spacing']);
                }

            $delay = (array_key_exists('delay', $frame)) ? $frame['delay'] : $this->_defaultDelay;

            ob_start();
            imagegif($image);
            $frames[]=ob_get_contents();
            $frame_delay[]=$delay; // Delay in the animation.
            ob_end_clean();
        }


        $repeat = (array_key_exists('repeat', $array)) ? $array['repeat'] : $this->_defaultRepeat;
        $gif = new GIFEncoder($frames,$frame_delay,$repeat,2,0,0,0,0,'bin');
        return $gif->GetAnimation();
    }

    /**
     * Creates an actual GIF image from the given source
     *
     * @param  string 	$imagePath path to the image
     * @return resource            returns the image
     */
    private function _createImage($imagePath) {
        $cImage = null;
        $tmp = explode('.', $imagePath);
        $ext = end($tmp);

        switch(strtolower($ext)){
            case 'jpg':
            case 'jpeg':
                $cImage = imagecreatefromjpeg($imagePath);
                break;
            case 'png':
                $cImage = imagecreatefrompng($imagePath);
                break;
        }

        return $cImage;
    }

    /**
     * Converts hexadecimal color string to an array with rgb values
     *
     * @param  string $hex the hexadecimal color which needs to be converted
     * @return array       returns an array with the rgb values
     */
    private function _hex2rgb($hex) {
        $hex = str_replace("#", "", $hex);

        if(strlen($hex) == 3) {
            $r = hexdec(substr($hex,0,1).substr($hex,0,1));
            $g = hexdec(substr($hex,1,1).substr($hex,1,1));
            $b = hexdec(substr($hex,2,1).substr($hex,2,1));
        } else {
            $r = hexdec(substr($hex,0,2));
            $g = hexdec(substr($hex,2,2));
            $b = hexdec(substr($hex,4,2));
        }
        $rgb = [$r, $g, $b];

        return $rgb;
    }
}
