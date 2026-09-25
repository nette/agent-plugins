# Working with Images

Contents:

- [Creating an Image](#creating-an-image)
- [Saving the Image](#saving-the-image)
- [Formats](#formats)
- [Resizing](#resizing)
- [Cropping](#cropping)
- [Colors (since 4.0.2)](#colors-since-402)
- [Drawing and Editing](#drawing-and-editing)
- [Merging Multiple Images](#merging-multiple-images)
- [Overview of Methods](#overview-of-methods)
  - [`static fromBlank(int $width, int $height, ?ImageColor $color=null): Image`](#static-fromblankint-width-int-height-imagecolor-colornull-image)
  - [`static fromFile(string $file, int &$detectedFormat=null, string &$warnings=null): Image`](#static-fromfilestring-file-int-detectedformatnull-string-warningsnull-image)
  - [`static fromString(string $s, int &$detectedFormat=null, string &$warnings=null): Image`](#static-fromstringstring-s-int-detectedformatnull-string-warningsnull-image)
  - [`static rgb(int $red, int $green, int $blue, int $transparency=0): array` (deprecated)](#static-rgbint-red-int-green-int-blue-int-transparency0-array-deprecated)
  - [`static typeToExtension(int $type): string`](#static-typetoextensionint-type-string)
  - [`static typeToMimeType(int $type): string`](#static-typetomimetypeint-type-string)
  - [`static extensionToType(string $extension): int`](#static-extensiontotypestring-extension-int)
  - [`static detectTypeFromFile(string $file, int &$width=null, int &$height=null): ?int`](#static-detecttypefromfilestring-file-int-widthnull-int-heightnull-int)
  - [`static detectTypeFromString(string $s, int &$width=null, int &$height=null): ?int`](#static-detecttypefromstringstring-s-int-widthnull-int-heightnull-int)
  - [`static isTypeSupported(int $type): bool`](#static-istypesupportedint-type-bool)
  - [`static getSupportedTypes(): array` (since 4.0.4)](#static-getsupportedtypes-array-since-404)
  - [`static calculateTextBox(string $text, string $fontFile, float $size, float $angle=0, array $options=[]): array`](#static-calculatetextboxstring-text-string-fontfile-float-size-float-angle0-array-options-array)
  - [`affine(array $affine, ?array $clip=null): Image`](#affinearray-affine-array-clipnull-image)
  - [`alphaBlending(bool $on): void`](#alphablendingbool-on-void)
  - [`antialias(bool $on): void`](#antialiasbool-on-void)
  - [`arc(int $centerX, int $centerY, int $width, int $height, int $startAngle, int $endAngle, ImageColor $color): void`](#arcint-centerx-int-centery-int-width-int-height-int-startangle-int-endangle-imagecolor-color-void)
  - [`colorAllocate(int $red, int $green, int $blue): int`](#colorallocateint-red-int-green-int-blue-int)
  - [`colorAllocateAlpha(int $red, int $green, int $blue, int $alpha): int`](#colorallocatealphaint-red-int-green-int-blue-int-alpha-int)
  - [`colorAt(int $x, int $y): int`](#coloratint-x-int-y-int)
  - [`colorClosest(int $red, int $green, int $blue): int`](#colorclosestint-red-int-green-int-blue-int)
  - [`colorClosestAlpha(int $red, int $green, int $blue, int $alpha): int`](#colorclosestalphaint-red-int-green-int-blue-int-alpha-int)
  - [`colorClosestHWB(int $red, int $green, int $blue): int`](#colorclosesthwbint-red-int-green-int-blue-int)
  - [`colorDeallocate(int $color): void`](#colordeallocateint-color-void)
  - [`colorExact(int $red, int $green, int $blue): int`](#colorexactint-red-int-green-int-blue-int)
  - [`colorExactAlpha(int $red, int $green, int $blue, int $alpha): int`](#colorexactalphaint-red-int-green-int-blue-int-alpha-int)
  - [`colorMatch(Image $image2): void`](#colormatchimage-image2-void)
  - [`colorResolve(int $red, int $green, int $blue): int`](#colorresolveint-red-int-green-int-blue-int)
  - [`colorResolveAlpha(int $red, int $green, int $blue, int $alpha): int`](#colorresolvealphaint-red-int-green-int-blue-int-alpha-int)
  - [`colorSet(int $index, int $red, int $green, int $blue, int $alpha=0): void`](#colorsetint-index-int-red-int-green-int-blue-int-alpha0-void)
  - [`colorsForIndex(int $index): array`](#colorsforindexint-index-array)
  - [`colorsTotal(): int`](#colorstotal-int)
  - [`colorTransparent(?int $color=null): int`](#colortransparentint-colornull-int)
  - [`convolution(array $matrix, float $div, float $offset): void`](#convolutionarray-matrix-float-div-float-offset-void)
  - [`copy(Image $src, int $dstX, int $dstY, int $srcX, int $srcY, int $srcW, int $srcH): void`](#copyimage-src-int-dstx-int-dsty-int-srcx-int-srcy-int-srcw-int-srch-void)
  - [`copyMerge(Image $src, int $dstX, int $dstY, int $srcX, int $srcY, int $srcW, int $srcH, int $opacity): void`](#copymergeimage-src-int-dstx-int-dsty-int-srcx-int-srcy-int-srcw-int-srch-int-opacity-void)
  - [`copyMergeGray(Image $src, int $dstX, int $dstY, int $srcX, int $srcY, int $srcW, int $srcH, int $opacity): void`](#copymergegrayimage-src-int-dstx-int-dsty-int-srcx-int-srcy-int-srcw-int-srch-int-opacity-void)
  - [`copyResampled(Image $src, int $dstX, int $dstY, int $srcX, int $srcY, int $dstW, int $dstH, int $srcW, int $srcH): void`](#copyresampledimage-src-int-dstx-int-dsty-int-srcx-int-srcy-int-dstw-int-dsth-int-srcw-int-srch-void)
  - [`copyResized(Image $src, int $dstX, int $dstY, int $srcX, int $srcY, int $dstW, int $dstH, int $srcW, int $srcH): void`](#copyresizedimage-src-int-dstx-int-dsty-int-srcx-int-srcy-int-dstw-int-dsth-int-srcw-int-srch-void)
  - [`crop(int|string $left, int|string $top, int|string $width, int|string $height): Image`](#cropintstring-left-intstring-top-intstring-width-intstring-height-image)
  - [`cropAuto(int $mode=0, float $threshold=.5, ?ImageColor $color=null): Image`](#cropautoint-mode0-float-threshold5-imagecolor-colornull-image)
  - [`ellipse(int $centerX, int $centerY, int $width, int $height, ImageColor $color): void`](#ellipseint-centerx-int-centery-int-width-int-height-imagecolor-color-void)
  - [`fill(int $x, int $y, ImageColor $color): void`](#fillint-x-int-y-imagecolor-color-void)
  - [`filledArc(int $centerX, int $centerY, int $width, int $height, int $startAngle, int $endAngle, ImageColor $color, int $style): void`](#filledarcint-centerx-int-centery-int-width-int-height-int-startangle-int-endangle-imagecolor-color-int-style-void)
  - [`filledEllipse(int $centerX, int $centerY, int $width, int $height, ImageColor $color): void`](#filledellipseint-centerx-int-centery-int-width-int-height-imagecolor-color-void)
  - [`filledPolygon(array $points, ImageColor $color): void`](#filledpolygonarray-points-imagecolor-color-void)
  - [`filledRectangle(int $x1, int $y1, int $x2, int $y2, ImageColor $color): void`](#filledrectangleint-x1-int-y1-int-x2-int-y2-imagecolor-color-void)
  - [`filledRectangleWH(int $x, int $y, int $width, int $height, ImageColor $color): void`](#filledrectanglewhint-x-int-y-int-width-int-height-imagecolor-color-void)
  - [`fillToBorder(int $x, int $y, ImageColor $borderColor, ImageColor $color): void`](#filltoborderint-x-int-y-imagecolor-bordercolor-imagecolor-color-void)
  - [`filter(int $filtertype, int ...$args): void`](#filterint-filtertype-int-args-void)
  - [`flip(int $mode): void`](#flipint-mode-void)
  - [`ftText(float $size, float $angle, int $x, int $y, ImageColor $color, string $fontFile, string $text, array $options=[]): array`](#fttextfloat-size-float-angle-int-x-int-y-imagecolor-color-string-fontfile-string-text-array-options-array)
  - [`gammaCorrect(float $inputgamma, float $outputgamma): void`](#gammacorrectfloat-inputgamma-float-outputgamma-void)
  - [`getClip(): array`](#getclip-array)
  - [`getHeight(): int`](#getheight-int)
  - [`getImageResource(): GdImage`](#getimageresource-gdimage)
  - [`getWidth(): int`](#getwidth-int)
  - [`interlace(?int $interlace=null): int`](#interlaceint-interlacenull-int)
  - [`isTrueColor(): bool`](#istruecolor-bool)
  - [`layerEffect(int $effect): void`](#layereffectint-effect-void)
  - [`line(int $x1, int $y1, int $x2, int $y2, ImageColor $color): void`](#lineint-x1-int-y1-int-x2-int-y2-imagecolor-color-void)
  - [`openPolygon(array $points, ImageColor $color): void`](#openpolygonarray-points-imagecolor-color-void)
  - [`paletteCopy(Image $source): void`](#palettecopyimage-source-void)
  - [`paletteToTrueColor(): void`](#palettetotruecolor-void)
  - [`place(Image $image, int|string $left=0, int|string $top=0, int $opacity=100): Image`](#placeimage-image-intstring-left0-intstring-top0-int-opacity100-image)
  - [`polygon(array $points, ImageColor $color): void`](#polygonarray-points-imagecolor-color-void)
  - [`rectangle(int $x1, int $y1, int $x2, int $y2, ImageColor $color): void`](#rectangleint-x1-int-y1-int-x2-int-y2-imagecolor-color-void)
  - [`rectangleWH(int $x, int $y, int $width, int $height, ImageColor $color): void`](#rectanglewhint-x-int-y-int-width-int-height-imagecolor-color-void)
  - [`resize(int|string $width, int|string $height, int $mode=Image::OrSmaller): Image`](#resizeintstring-width-intstring-height-int-modeimageorsmaller-image)
  - [`resolution(?int $resX=null, ?int $resY=null): mixed`](#resolutionint-resxnull-int-resynull-mixed)
  - [`rotate(float $angle, ImageColor $backgroundColor): Image`](#rotatefloat-angle-imagecolor-backgroundcolor-image)
  - [`save(string $file, ?int $quality=null, ?int $type=null): void`](#savestring-file-int-qualitynull-int-typenull-void)
  - [`saveAlpha(bool $saveflag): void`](#savealphabool-saveflag-void)
  - [`scale(int $newWidth, int $newHeight=-1, int $mode=IMG_BILINEAR_FIXED): Image`](#scaleint-newwidth-int-newheight-1-int-modeimg_bilinear_fixed-image)
  - [`send(int $type=ImageType::JPEG, ?int $quality=null): void`](#sendint-typeimagetypejpeg-int-qualitynull-void)
  - [`setBrush(Image $brush): void`](#setbrushimage-brush-void)
  - [`setClip(int $x1, int $y1, int $x2, int $y2): void`](#setclipint-x1-int-y1-int-x2-int-y2-void)
  - [`setInterpolation(int $method=IMG_BILINEAR_FIXED): void`](#setinterpolationint-methodimg_bilinear_fixed-void)
  - [`getInterpolation(): int`](#getinterpolation-int)
  - [`setPixel(int $x, int $y, ImageColor $color): void`](#setpixelint-x-int-y-imagecolor-color-void)
  - [`setStyle(array $style): void`](#setstylearray-style-void)
  - [`setThickness(int $thickness): void`](#setthicknessint-thickness-void)
  - [`setTile(Image $tile): void`](#settileimage-tile-void)
  - [`sharpen(): Image`](#sharpen-image)
  - [`toString(int $type=ImageType::JPEG, ?int $quality=null): string`](#tostringint-typeimagetypejpeg-int-qualitynull-string)
  - [`trueColorToPalette(bool $dither, int $ncolors): void`](#truecolortopalettebool-dither-int-ncolors-void)
  - [`ttfText(float $size, float $angle, int $x, int $y, ImageColor $color, string $fontFile, string $text, array $options=[]): array`](#ttftextfloat-size-float-angle-int-x-int-y-imagecolor-color-string-fontfile-string-text-array-options-array)


The [Nette\Utils\Image](https://api.nette.org/utils/master/Nette/Utils/Image.html) class simplifies image manipulation, such as resizing, cropping, sharpening, drawing, or merging multiple images.

PHP has an extensive set of functions for image manipulation. However, their API isn't very user-friendly. It wouldn't be Nette Framework if it didn't come up with a delightful API.

Installation:

```shell
composer require nette/utils
```

The following examples assume the following class alias is defined:

```php
use Nette\Utils\Image;
use Nette\Utils\ImageColor;
use Nette\Utils\ImageType;
```

## Creating an Image

Create a new true color image, for example, with dimensions 100×200:

```php
$image = Image::fromBlank(100, 200);
```

Optionally, you can specify the background color (default is black):

```php
$image = Image::fromBlank(100, 200, ImageColor::rgb(125, 0, 0));
```

Or load an image from a file:

```php
$image = Image::fromFile('nette.jpg');
```

## Saving the Image

The image can be saved to a file:

```php
$image->save('resampled.jpg');
```

You can specify the compression quality in the range 0–100 for JPEG (default 85), WEBP (default 80), and AVIF (default 30), and 0–9 for PNG (default 9):

```php
$image->save('resampled.jpg', 80); // JPEG, quality 80%
```

If the format is not clear from the file extension, it can be specified using a [constant](#formats):

```php
$image->save('resampled.tmp', null, ImageType::JPEG);
```

Instead of saving to disk, the image can be written to a variable:

```php
$data = $image->toString(ImageType::JPEG, 80); // JPEG, quality 80%
```

or sent directly to the browser with the appropriate `Content-Type` HTTP header:

```php
// sends the Content-Type: image/png header
$image->send(ImageType::PNG);
```

## Formats

Supported formats are JPEG, PNG, GIF, WebP, AVIF, and BMP. However, your PHP version must also support them, which you can verify using the [isTypeSupported()](#static-istypesupportedint-type-bool) function. Animations are not supported.

The formats are represented by the constants `ImageType::JPEG`, `ImageType::PNG`, `ImageType::GIF`, `ImageType::WEBP`, `ImageType::AVIF`, and `ImageType::BMP`.

```php
$supported = Image::isTypeSupported(ImageType::JPEG);
```

Need to detect the image format upon loading? The method returns it in the second parameter:

```php
$image = Image::fromFile('nette.jpg', $type);
```

Detection without loading the image itself is done by `Image::detectTypeFromFile()`.

## Resizing

A common operation is resizing an image. The current dimensions are returned by the `getWidth()` and `getHeight()` methods.

The `resize()` method is used for resizing. Example of proportional resizing so that the image does not exceed 500×300 pixels (either the width will be exactly 500px or the height will be exactly 300px; one dimension is calculated to maintain the aspect ratio):

```php
$image->resize(500, 300);
```

It's possible to specify only one dimension, and the other will be calculated automatically:

```php
$image->resize(500, null); // width 500px, height calculated automatically

$image->resize(null, 300); // width calculated automatically, height 300px
```

Either dimension can also be specified in percentages:

```php
$image->resize('75%', 300); // 75 % × 300px
```

The behavior of `resize()` can be influenced by the following flags. All flags except `Image::Stretch` preserve the aspect ratio.

| Flag | Description |
| --- | --- |
| `Image::OrSmaller` (default) | the resulting dimensions will be less than or equal to the requested dimensions |
| `Image::OrBigger` | fills (and possibly exceeds in one dimension) the target area |
| `Image::Cover` | fills the target area and crops anything that exceeds it |
| `Image::ShrinkOnly` | only shrinks (prevents stretching a small image) |
| `Image::Stretch` | does not preserve the aspect ratio |

Flags are passed as the third argument to the method:

```php
$image->resize(500, 300, Image::OrBigger);
```

Flags can be combined:

```php
$image->resize(500, 300, Image::ShrinkOnly | Image::Stretch);
```

Images can be flipped vertically or horizontally by specifying one of the dimensions (or both) as a negative number:

```php
$flipped = $image->resize(null, '-100%'); // flip vertically

$flipped = $image->resize('-100%', '-100%'); // rotate 180°

$flipped = $image->resize(-125, 500); // resize & flip horizontally
```

After resizing an image, you can enhance its appearance with subtle sharpening:

```php
$image->sharpen();
```

## Cropping

The `crop()` method is used for cropping:

```php
$image->crop($left, $top, $width, $height);
```

Similar to `resize()`, all values can be specified in percentages. Percentages for `$left` and `$top` are calculated from the remaining space, similar to the CSS `background-position` property:

```php
$image->crop('100%', '50%', '80%', '80%');
```

![](https://files.nette.org/git/utils/4.x/crop.svg)

The image can also be cropped automatically, for example, to remove black borders:

```php
$image->cropAuto(IMG_CROP_BLACK);
```

The `cropAuto()` method is an object-oriented wrapper for the `imagecropauto()` function; see [its documentation](https://www.php.net/manual/en/function.imagecropauto) for more information.

## Colors (since 4.0.2)

The `ImageColor::rgb()` method allows you to define a color using red, green, and blue (RGB) values. Optionally, you can also specify a transparency value ranging from 0 (completely transparent) to 1 (fully opaque), just like in CSS.

```php
$color = ImageColor::rgb(255, 0, 0); // Red
$transparentBlue = ImageColor::rgb(0, 0, 255, 0.5); // Semi-transparent blue
```

The `ImageColor::hex()` method allows you to define a color using the hexadecimal format, similar to CSS. It supports the formats `#rgb`, `#rrggbb`, `#rgba`, and `#rrggbbaa`:

```php
$color = ImageColor::hex("#F00"); // Red
$transparentGreen = ImageColor::hex("#00FF0080"); // Semi-transparent green
```

Colors can be used in other methods, such as `ellipse()`, `fill()`, etc.

## Drawing and Editing

All PHP functions for image manipulation are available to you, see [Overview of Methods](#overview-of-methods), but wrapped in an object-oriented style:

```php
$image->filledEllipse($centerX, $centerY, $width, $height, ImageColor::rgb(255, 0, 0));
```

Because PHP's native functions for drawing rectangles are somewhat impractical due to coordinate specification, the `Image` class offers replacements: [rectangleWH()](#rectanglewhint-x-int-y-int-width-int-height-imagecolor-color-void) and [filledRectangleWH()](#filledrectanglewhint-x-int-y-int-width-int-height-imagecolor-color-void).

## Merging Multiple Images

You can easily place another image onto the current one:

```php
$logo = Image::fromFile('logo.png');
$blank = Image::fromBlank(320, 240, ImageColor::rgb(52, 132, 210));

// coordinates can also be specified in percentages
$blank->place($logo, '80%', '80%'); // place near the bottom right corner
```

When placing, the alpha channel is respected. Additionally, you can influence the transparency of the placed image (creating a watermark):

```php
$blank->place($image, '80%', '80%', 25); // opacity is 25%
```

Using such an API is truly a pleasure!

## Overview of Methods

### `static fromBlank(int $width, int $height, ?ImageColor $color=null): Image`

Creates a new true color image of the given dimensions. The default color is black.

### `static fromFile(string $file, int &$detectedFormat=null, string &$warnings=null): Image`

Reads an image from a file and returns its [type](#formats) in `$detectedFormat`. If you pass `$warnings`, any non-fatal warnings from the image decoder are stored in it instead of being raised as a PHP warning; it stays `null` when there are none.

### `static fromString(string $s, int &$detectedFormat=null, string &$warnings=null): Image`

Reads an image from a string and returns its [type](#formats) in `$detectedFormat`. If you pass `$warnings`, any non-fatal warnings from the image decoder are stored in it instead of being raised as a PHP warning; it stays `null` when there are none.

### `static rgb(int $red, int $green, int $blue, int $transparency=0): array` (deprecated)

This function has been replaced by the `ImageColor` class, see [Colors](#colors-since-402).

### `static typeToExtension(int $type): string`

Returns the file extension for the given [type](#formats).

### `static typeToMimeType(int $type): string`

Returns the MIME type for the given [type](#formats).

### `static extensionToType(string $extension): int`

Returns the image [type](#formats) based on the file extension.

### `static detectTypeFromFile(string $file, int &$width=null, int &$height=null): ?int`

Returns the [type](#formats) of the image file and, in the `$width` and `$height` parameters, also its dimensions.

### `static detectTypeFromString(string $s, int &$width=null, int &$height=null): ?int`

Returns the [type](#formats) of the image from a string and, in the `$width` and `$height` parameters, also its dimensions.

### `static isTypeSupported(int $type): bool`

Checks if the given image [type](#formats) is supported.

### `static getSupportedTypes(): array` (since 4.0.4)

Returns an array of supported image [types](#formats).

### `static calculateTextBox(string $text, string $fontFile, float $size, float $angle=0, array $options=[]): array`

Calculates the dimensions of the rectangle bounding the text in a specific font and size. Returns an associative array containing the keys `left`, `top`, `width`, `height`. The left margin can be negative if the text starts with a left kerning overhang.

### `affine(array $affine, ?array $clip=null): Image`

Returns an image containing the affine transformed source image, using an optional clipping area. ([more](https://www.php.net/manual/en/function.imageaffine)).

### `alphaBlending(bool $on): void`

Allows for two different modes of drawing on truecolor images. In blending mode, the alpha channel component of the color supplied to all drawing functions, such as `setPixel()`, determines how much of the underlying color should be allowed to shine through. As a result, it automatically blends the existing color at that point with the drawing color, and stores the result in the image. The resulting pixel is opaque. In non-blending mode, the drawing color is copied literally with its alpha channel information, replacing the destination pixel. Blending mode is not available when drawing on palette images. ([more](https://www.php.net/manual/en/function.imagealphablending))

### `antialias(bool $on): void`

Activate the fast drawing antialiased methods for lines and wired polygons. It does not support alpha components. It works using a direct blend operation. It works only with truecolor images.

Using antialiased primitives with transparent background color can end with some unexpected results. The blend method uses the background color as any other colors. The lack of alpha component support does not allow an alpha based antialiasing method. ([more](https://www.php.net/manual/en/function.imageantialias))

### `arc(int $centerX, int $centerY, int $width, int $height, int $startAngle, int $endAngle, ImageColor $color): void`

Draws a circular arc centered at the given coordinates. ([more](https://www.php.net/manual/en/function.imagearc))

### `colorAllocate(int $red, int $green, int $blue): int`

Returns a color identifier representing the color composed of the given RGB components. It must be called to create each color that is to be used in the image. ([more](https://www.php.net/manual/en/function.imagecolorallocate))

### `colorAllocateAlpha(int $red, int $green, int $blue, int $alpha): int`

Behaves identically to `colorAllocate()` with the addition of the transparency parameter `$alpha`. ([more](https://www.php.net/manual/en/function.imagecolorallocatealpha))

### `colorAt(int $x, int $y): int`

Returns the color index of the pixel at the specified location in the image. If the image is a truecolor image, this function returns the RGB value of that pixel as an integer. Use bit shifting and masking to access the distinct red, green, and blue component values: ([more](https://www.php.net/manual/en/function.imagecolorat))

### `colorClosest(int $red, int $green, int $blue): int`

Returns the index of the color in the image's palette that is “closest” to the specified RGB value. The “distance” between the desired color and each color in the palette is calculated as if the RGB values represented points in three-dimensional space. ([more](https://www.php.net/manual/en/function.imagecolorclosest))

### `colorClosestAlpha(int $red, int $green, int $blue, int $alpha): int`

Returns the index of the color in the image's palette that is “closest” to the specified RGB value and `$alpha` level. ([more](https://www.php.net/manual/en/function.imagecolorclosestalpha))

### `colorClosestHWB(int $red, int $green, int $blue): int`

Gets the index of the color that has the hue, white, and blackness nearest to the given color. ([more](https://www.php.net/manual/en/function.imagecolorclosesthwb))

### `colorDeallocate(int $color): void`

Deallocates a color previously allocated with `colorAllocate()` or `colorAllocateAlpha()`. ([more](https://www.php.net/manual/en/function.imagecolordeallocate))

### `colorExact(int $red, int $green, int $blue): int`

Returns the index of the specified color in the image's palette. ([more](https://www.php.net/manual/en/function.imagecolorexact))

### `colorExactAlpha(int $red, int $green, int $blue, int $alpha): int`

Returns the index of the specified color + alpha in the image's palette. ([more](https://www.php.net/manual/en/function.imagecolorexactalpha))

### `colorMatch(Image $image2): void`

Makes the colors of the palette version of an image more closely match the true color version. ([more](https://www.php.net/manual/en/function.imagecolormatch))

### `colorResolve(int $red, int $green, int $blue): int`

Returns a color index for a requested color, either the exact color or the closest possible alternative. ([more](https://www.php.net/manual/en/function.imagecolorresolve))

### `colorResolveAlpha(int $red, int $green, int $blue, int $alpha): int`

Returns a color index for a requested color, either the exact color or the closest possible alternative. ([more](https://www.php.net/manual/en/function.imagecolorresolvealpha))

### `colorSet(int $index, int $red, int $green, int $blue, int $alpha=0): void`

Sets the specified index in the palette to the specified color. ([more](https://www.php.net/manual/en/function.imagecolorset))

### `colorsForIndex(int $index): array`

Gets the color for a specified index. ([more](https://www.php.net/manual/en/function.imagecolorsforindex))

### `colorsTotal(): int`

Returns the number of colors in the image palette. ([more](https://www.php.net/manual/en/function.imagecolorstotal))

### `colorTransparent(?int $color=null): int`

Gets or sets the transparent color in the image. ([more](https://www.php.net/manual/en/function.imagecolortransparent))

### `convolution(array $matrix, float $div, float $offset): void`

Applies a convolution matrix to the image, using the given coefficient and offset. ([more](https://www.php.net/manual/en/function.imageconvolution))

Requires the *Bundled GD extension*, so it might not work everywhere.

### `copy(Image $src, int $dstX, int $dstY, int $srcX, int $srcY, int $srcW, int $srcH): void`

Copies a part of `$src` onto image starting at the coordinates `$srcX`, `$srcY` with a width of `$srcW` and a height of `$srcH`. The portion defined will be copied onto the coordinates, `$dstX` and `$dstY`. ([more](https://www.php.net/manual/en/function.imagecopy))

### `copyMerge(Image $src, int $dstX, int $dstY, int $srcX, int $srcY, int $srcW, int $srcH, int $opacity): void`

Copies a part of `$src` onto image starting at the coordinates `$srcX`, `$srcY` with a width of `$srcW` and a height of `$srcH`. The portion defined will be copied onto the coordinates, `$dstX` and `$dstY`. ([more](https://www.php.net/manual/en/function.imagecopymerge))

### `copyMergeGray(Image $src, int $dstX, int $dstY, int $srcX, int $srcY, int $srcW, int $srcH, int $opacity): void`

Copies a part of `$src` onto image starting at the coordinates `$srcX`, `$srcY` with a width of `$srcW` and a height of `$srcH`. The portion defined will be copied onto the coordinates, `$dstX` and `$dstY`.

This function is identical to `copyMerge()` except that when merging it preserves the hue of the source by converting the destination pixels to gray scale before the copy operation. ([more](https://www.php.net/manual/en/function.imagecopymergegray))

### `copyResampled(Image $src, int $dstX, int $dstY, int $srcX, int $srcY, int $dstW, int $dstH, int $srcW, int $srcH): void`

Copies a rectangular portion of one image to another image, smoothly interpolating pixel values so that, in particular, reducing the size of an image still retains a great deal of clarity.

In other words, `copyResampled()` will take a rectangular area from `$src` of width `$srcW` and height `$srcH` at position (`$srcX`,`$srcY`) and place it in a rectangular area of image of width `$dstW` and height `$dstH` at position (`$dstX`,`$dstY`).

If the source and destination coordinates and width and heights differ, appropriate stretching or shrinking of the image fragment will be performed. The coordinates refer to the upper left corner. This function can be used to copy regions within the same image but if the regions overlap the results will be unpredictable. ([more](https://www.php.net/manual/en/function.imagecopyresampled))

### `copyResized(Image $src, int $dstX, int $dstY, int $srcX, int $srcY, int $dstW, int $dstH, int $srcW, int $srcH): void`

Copies a rectangular portion of one image to another image. In other words, `copyResized()` will take a rectangular area from `$src` of width `$srcW` and height `$srcH` at position (`$srcX`,`$srcY`) and place it in a rectangular area of image of width `$dstW` and height `$dstH` at position (`$dstX`,`$dstY`).

If the source and destination coordinates and width and heights differ, appropriate stretching or shrinking of the image fragment will be performed. The coordinates refer to the upper left corner. This function can be used to copy regions within the same image but if the regions overlap the results will be unpredictable. ([more](https://www.php.net/manual/en/function.imagecopyresized))

### `crop(int|string $left, int|string $top, int|string $width, int|string $height): Image`

Crops an image to the given rectangular area. Dimensions can be specified as integers in pixels or as strings in percentages (e.g., `'50%'`).

### `cropAuto(int $mode=0, float $threshold=.5, ?ImageColor $color=null): Image`

Automatically crops an image according to the given `$mode`. ([more](https://www.php.net/manual/en/function.imagecropauto))

### `ellipse(int $centerX, int $centerY, int $width, int $height, ImageColor $color): void`

Draws an ellipse centered at the specified coordinates. ([more](https://www.php.net/manual/en/function.imageellipse))

### `fill(int $x, int $y, ImageColor $color): void`

Performs a flood fill starting at the given coordinate (top left is 0, 0) with the given `$color`. ([more](https://www.php.net/manual/en/function.imagefill))

### `filledArc(int $centerX, int $centerY, int $width, int $height, int $startAngle, int $endAngle, ImageColor $color, int $style): void`

Draws a partial arc centered at the specified coordinates. ([more](https://www.php.net/manual/en/function.imagefilledarc))

### `filledEllipse(int $centerX, int $centerY, int $width, int $height, ImageColor $color): void`

Draws an ellipse centered at the specified coordinates. ([more](https://www.php.net/manual/en/function.imagefilledellipse))

### `filledPolygon(array $points, ImageColor $color): void`

Creates a filled polygon in the image. ([more](https://www.php.net/manual/en/function.imagefilledpolygon))

### `filledRectangle(int $x1, int $y1, int $x2, int $y2, ImageColor $color): void`

Creates a rectangle filled with `$color` in the image, starting at point (`$x1`, `$y1`) and ending at (`$x2`, `$y2`). Point (0, 0) is the top-left corner of the image. ([more](https://www.php.net/manual/en/function.imagefilledrectangle))

### `filledRectangleWH(int $x, int $y, int $width, int $height, ImageColor $color): void`

Creates a rectangle filled with `$color` in the image, starting at point (`$x`, `$y`) with width `$width` and height `$height`. Point (0, 0) is the top-left corner of the image.

### `fillToBorder(int $x, int $y, ImageColor $borderColor, ImageColor $color): void`

Performs a flood fill whose border color is defined by `$borderColor`. The starting point for the fill is (`$x`, `$y`) (top-left is 0, 0), and the region is filled with the color `$color`. ([more](https://www.php.net/manual/en/function.imagefilltoborder))

### `filter(int $filtertype, int ...$args): void`

Applies the given filter `$filtertype` to the image. ([more](https://www.php.net/manual/en/function.imagefilter))

### `flip(int $mode): void`

Flips the image using the given `$mode`. ([more](https://www.php.net/manual/en/function.imageflip))

### `ftText(float $size, float $angle, int $x, int $y, ImageColor $color, string $fontFile, string $text, array $options=[]): array`

Writes text to the image. ([more](https://www.php.net/manual/en/function.imagefttext))

### `gammaCorrect(float $inputgamma, float $outputgamma): void`

Applies gamma correction to the image given an input and an output gamma. ([more](https://www.php.net/manual/en/function.imagegammacorrect))

### `getClip(): array`

Retrieves the current clipping rectangle, i.e., the area beyond which no pixels will be drawn. ([more](https://www.php.net/manual/en/function.imagegetclip))

### `getHeight(): int`

Returns the height of the image.

### `getImageResource(): GdImage`

Returns the underlying GD image resource.

### `getWidth(): int`

Returns the width of the image.

### `interlace(?int $interlace=null): int`

Turns interlacing on or off. If interlacing is enabled and the image is saved as JPEG, it will be saved as a progressive JPEG. ([more](https://www.php.net/manual/en/function.imageinterlace))

### `isTrueColor(): bool`

Checks if the image is a truecolor image. ([more](https://www.php.net/manual/en/function.imageistruecolor))

### `layerEffect(int $effect): void`

Sets the alpha blending flag to use layering effects. ([more](https://www.php.net/manual/en/function.imagelayereffect))

### `line(int $x1, int $y1, int $x2, int $y2, ImageColor $color): void`

Draws a line between the two given points. ([more](https://www.php.net/manual/en/function.imageline))

### `openPolygon(array $points, ImageColor $color): void`

Draws an open polygon on the image. Unlike `polygon()`, no line is drawn between the last and the first point. ([more](https://www.php.net/manual/en/function.imageopenpolygon))

### `paletteCopy(Image $source): void`

Copies the palette from `$source` to the image. ([more](https://www.php.net/manual/en/function.imagepalettecopy))

### `paletteToTrueColor(): void`

Converts a palette-based image to a truecolor image. ([more](https://www.php.net/manual/en/function.imagepalettetotruecolor))

### `place(Image $image, int|string $left=0, int|string $top=0, int $opacity=100): Image`

Copies `$image` onto the current image at coordinates (`$left`, `$top`). Coordinates can be specified as integers in pixels or as strings in percentages (e.g., `'50%'`).

### `polygon(array $points, ImageColor $color): void`

Creates a polygon in the image. ([more](https://www.php.net/manual/en/function.imagepolygon))

### `rectangle(int $x1, int $y1, int $x2, int $y2, ImageColor $color): void`

Creates a rectangle at the specified coordinates. ([more](https://www.php.net/manual/en/function.imagerectangle))

### `rectangleWH(int $x, int $y, int $width, int $height, ImageColor $color): void`

Creates a rectangle at the given coordinates using width and height.

### `resize(int|string $width, int|string $height, int $mode=Image::OrSmaller): Image`

Resizes an image, see [more info](#resizing). Dimensions can be specified as integers in pixels or as strings in percentages (e.g., `'50%'`).

### `resolution(?int $resX=null, ?int $resY=null): mixed`

Allows you to set and get the resolution of an image in DPI (dots per inch). If none of the optional parameters is given, the current resolution is returned as indexed array. If only `$resX` is given, the horizontal and vertical resolution are set to this value. If both optional parameters are given, the horizontal and vertical resolution are set to these values, respectively.

The resolution is only used as meta information when images are read from and written to formats supporting this kind of information (currently PNG and JPEG). It does not affect any drawing operations. The default resolution for new images is 96 DPI. ([more](https://www.php.net/manual/en/function.imageresolution))

### `rotate(float $angle, ImageColor $backgroundColor): Image`

Rotates the image by the given `$angle` in degrees. The center of rotation is the center of the image, and the rotated image may have different dimensions than the original image. ([more](https://www.php.net/manual/en/function.imagerotate))

Requires the *Bundled GD extension*, so it might not work everywhere.

### `save(string $file, ?int $quality=null, ?int $type=null): void`

Saves the image to a file.

Compression quality is in the range 0–100 for JPEG (default 85), WEBP (default 80), and AVIF (default 30), and 0–9 for PNG (default 9). If the type is not clear from the file extension, you can specify it using one of the `ImageType` constants.

### `saveAlpha(bool $saveflag): void`

Sets the flag determining whether to save full alpha channel information (as opposed to single-color transparency) when saving PNG images.

Nette turns this flag on in the `Image` constructor, so an image loaded or created through `fromFile()`, `fromString()` or `fromBlank()` keeps its alpha channel. Note that with the default blending mode the alpha of a drawn color is only used to mix the result and is not stored, so call `alphaBlending(false)` first if the drawn transparency has to survive. ([more](https://www.php.net/manual/en/function.imagesavealpha))

### `scale(int $newWidth, int $newHeight=-1, int $mode=IMG_BILINEAR_FIXED): Image`

Scales an image using the given interpolation algorithm. ([more](https://www.php.net/manual/en/function.imagescale))

### `send(int $type=ImageType::JPEG, ?int $quality=null): void`

Outputs the image to the browser.

Compression quality is in the range 0–100 for JPEG (default 85), WEBP (default 80), and AVIF (default 30), and 0–9 for PNG (default 9).

### `setBrush(Image $brush): void`

Sets the brush image to be used by all line drawing functions (such as `line()` and `polygon()`) when drawing with the special colors `IMG_COLOR_BRUSHED` or `IMG_COLOR_STYLEDBRUSHED`. ([more](https://www.php.net/manual/en/function.imagesetbrush))

### `setClip(int $x1, int $y1, int $x2, int $y2): void`

Sets the current clipping rectangle, i.e., the area beyond which no pixels will be drawn. ([more](https://www.php.net/manual/en/function.imagesetclip))

### `setInterpolation(int $method=IMG_BILINEAR_FIXED): void`

Sets the interpolation method, which affects the `rotate()` and `affine()` methods. ([more](https://www.php.net/manual/en/function.imagesetinterpolation))

### `getInterpolation(): int`

Returns the current interpolation method used by the `rotate()` and `affine()` methods.

### `setPixel(int $x, int $y, ImageColor $color): void`

Draws a pixel at the specified coordinate. ([more](https://www.php.net/manual/en/function.imagesetpixel))

### `setStyle(array $style): void`

Sets the style to be used by all line drawing functions (such as `line()` and `polygon()`) when drawing with the special color `IMG_COLOR_STYLED` or lines of images with the color `IMG_COLOR_STYLEDBRUSHED`. ([more](https://www.php.net/manual/en/function.imagesetstyle))

### `setThickness(int $thickness): void`

Sets the thickness of lines drawn when drawing rectangles, polygons, arcs, etc., to `$thickness` pixels. ([more](https://www.php.net/manual/en/function.imagesetthickness))

### `setTile(Image $tile): void`

Sets the tile image to be used by all region filling functions (such as `fill()` and `filledPolygon()`) when filling with the special color `IMG_COLOR_TILED`.

A tile is an image used to fill an area with a repeated pattern. Any image can be used as a tile, and by setting the transparent color index of the tile image with `colorTransparent()`, a tile allowing certain parts of the underlying area to shine through can be created. ([more](https://www.php.net/manual/en/function.imagesettile))

### `sharpen(): Image`

Sharpens the image.

Requires the *Bundled GD extension*, so it might not work everywhere.

### `toString(int $type=ImageType::JPEG, ?int $quality=null): string`

Outputs the image as a string.

Compression quality is in the range 0–100 for JPEG (default 85), WEBP (default 80), and AVIF (default 30), and 0–9 for PNG (default 9).

### `trueColorToPalette(bool $dither, int $ncolors): void`

Converts a truecolor image to a palette image. ([more](https://www.php.net/manual/en/function.imagetruecolortopalette))

### `ttfText(float $size, float $angle, int $x, int $y, ImageColor $color, string $fontFile, string $text, array $options=[]): array`

Writes the given text into the image. ([more](https://www.php.net/manual/en/function.imagettftext))
