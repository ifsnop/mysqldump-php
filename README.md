# Resizer

Simple image resizing bundle for Laravel PHP framework.

This bundle will allow you to resize an uploaded image or any image from the file system with ease. Supported image types are: JPG/JPEG, PNG, GIF.

## Installation

Install Resizer using artisan:

    php artisan bundle:install resizer

Then in your *application/bundles.php* file, add the following line to load Resizer automatically:

    return array(
        'resizer' => array( 'auto' => true )
    );

Or without the `'auto' => true` to load it on demand:

    return array(
        'resizer'
    );

## Usage

In your view files, you'd add a file input element.

    <?php Input::file('picture') ?>

In your routes.php file or in any of your controller files, you can start the Resizer bundle if you haven't set it to auto-load by calling:

    Bundle::start('resizer');

Then you can start resizing your images by simply calling:

    Resizer::open( mixed $file )
        ->resize( int $width , int $height , string 'exact, portrait, landscape, auto or crop' )
        ->save( string 'path/to/file.jpg' , int $quality );

## Example

    Route::post('image/update', function() {
        $img = Input::file('picture');
        
        // Save a thumbnail
        $success = Resizer::open( $img )
            ->resize( 200 , 200 , 'crop' )
            ->save( 'images/my-new-filename.jpg' , 90 );
        
        if ( $success ) {
            return 'woohoo';
        } else {
            return 'lame';
        }
    });

## Credits

The image resize class was originally written in a tutorial by Jarrod Oberto on [NetTuts+](http://net.tutsplus.com/tutorials/php/image-resizing-made-easy-with-php/). I only modified it to use Laravel's File class, updated the coding style, added comments throughout the class file and turned it into a Laravel bundle.

Enjoy.