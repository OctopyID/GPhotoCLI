# Google Photo CLI

GPhoto streamlines the photo uploading process and helps you keep your photo library organized. Say goodbye to manual
photo uploads and hello to efficient photo management with
GPhoto.

## Features

- **No Duplicate Processing** - GPhoto uses a cache with keys based on hash files to ensure that the same files are not
  processed repeatedly, saving you time and storage.
- **No Duplicate Album Names** - GPhoto fetches album names from Google and compares them to ensure there are no
  duplicates, helping you keep your photo library organized and easy
  to navigate.
- **Automatic Token Refresh** - GPhoto automatically refreshes the token if an error occurs during the photo upload
  process, ensuring a seamless and uninterrupted uploading
  experience.
- **Secure Local Storage** - All authentication data and cache is securely stored locally to ensure your privacy and
  security. GPhoto does not collect any information from you.

## Installation

Please make sure you have the following requirements installed below:

- PHP 8.1 with curl, inotify, pcntl, and bcmatch extensions.

Download the latest release from the [releases page](https://github.com/OctopyID/GPhotoCLI/releases) or clone this
repository.

Due to hardware limitations of the Apple Silicon chipsets on macOS, the `ext-inotify` extension is not supported. Consequently, we offer two different build variants to handle this
situation:

1. **Non-inotify Variant:** This is a standard variant that doesn't rely on the `inotify` extension. It involves the following step:
    - When a new token is generated, the current process should be manually stopped.
    - After stopping the process, rerun the upload command to recognize and use the new token in the build process.


2. **Inotify Variant:** This variant provides an automated process, leveraging the 'inotify' feature available on Linux. It works as follows:
    - The build process automatically detects when a new token is generated - without the need for human intervention.
    - Therefore, there's no need to manually stop and rerun the process - saving time and reducing possible errors.

Please note that the Inotify variant offers a more efficient and seamless experience but it is not compatible with macOS environment that use Apple Silicon chipsets. On the other
hand, this variant is highly recommended for Linux users who have the `inotify` extension installed and enabled.

## Usage

Before using this application, you need to create a Google Cloud Platform project and enable the Google Photos Library
API and create
a [OAuth 2.0 Client ID](https://developers.google.com/photos/library/guides/overview#authorization).

The authentication file should look like this :

```json
{
    "web": {
        "client_id": "xxxxxxx.apps.googleusercontent.com",
        "project_id": "xxxxxxx",
        "auth_uri": "https://accounts.google.com/o/oauth2/auth",
        "token_uri": "https://oauth2.googleapis.com/token",
        "auth_provider_x509_cert_url": "https://www.googleapis.com/oauth2/v1/certs",
        "client_secret": "xxxxxxx",
        "redirect_uris": [
            "http://localhost:3000"
        ]
    }
}
```

### 1. Authorization Token

```bash
php gphoto auth:create --auth=./path/to/auth.json --listen=http://localhost:3000
```

If your listener is behind a proxy, you can specify a different redirect URI.

```bash
php gphoto auth:create --auth=./path/to/auth.json --listen=localhost:3000 --redirect=https://yourdomain.com
```

> [!NOTE]
> Make sure this listener/redirect URI is also registered in your Google OAuth configuration


You can also create a custom profile by providing a profile name as the first argument:

```bash
php gphoto auth:create personal --auth=./path/to/auth.json --listen=localhost:3000
```

The expired token can be renewed with the following command

```bash
php gphoto auth:reload
```

Or for a specific profile:

```bash
php gphoto auth:reload personal

```

### 2. Photos

By default, it uses the "default" profile, but you can use a different profile if needed by adding `--auth=personal` to the arguments in all the following commands

#### 2.1 Upload Single File

```bash
php gphoto upload:photo ./path/to/photo.png
```

or you can use ` --album` to upload to a specific album.

```bash
php gphoto upload:photo ./path/to/MyPicture.png --album="My Album"
```

#### 2.2 Upload Multiple Files

To upload multiple files, you need to create a directory that contains all photos with the following structure:

```
. Pictures
├── MyPicture 1.jpg
├── MyPicture 2.jpg
├── MyPicture 3.jpg
└── MyPicture 4.jpg
```

```bash
php gphoto upload:photo ./path/to/Pictures
```

or you can use ` --album` to upload to a specific album.

```bash
php gphoto upload:photo ./path/to/Pictures --album="My Album"
```

### 3. Albums

#### 3.1 Show List of Albums

```bash
php gphoto list:albums
``` 

#### 3.2 Upload Single Album

To upload a single album, you need to create a directory that contains all photos with the following structure:

```
. MyAlbum
├── MyPicture 1.jpg
├── MyPicture 2.jpg
├── MyPicture 3.jpg
├── MyPicture 4.jpg
└── MyPicture 5.jpg
```

```bash
php gphoto upload:album ./path/to/MyAlbum
```

This will upload all files in the `MyAlbum` directory to Google Photos and create an album with the `MyAlbum` name.

#### 3.3 Upload Multiple Albums

To upload multiple albums, you need to create a directory that contains all albums with the following structure:

```
. MyAlbumCollection
├── MyAlbum 1
│   ├── MyPicture 1.jpg
│   ├── MyPicture 2.jpg
│   ├── MyPicture 3.jpg
│   └── MyPicture 4.jpg
│
└── MyAlbum 2
    ├── MyPicture 1.jpg
    ├── MyPicture 2.jpg
    ├── MyPicture 3.jpg
    └── MyPicture 4.jpg
```

```bash
php gphoto upload:album ./path/to/MyAlbumCollection --multiple
```

This will upload all files in the `MyAlbumCollection` directory to Google Photos and create an album with `MyAlbum 1`
and `MyAlbum 2` names.

## Credits

- [Supian M](https://github.com/SupianIDz)
- [Octopy ID](https://github.com/OctopyID)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
