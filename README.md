# Google Photo CLI

GPhoto streamlines the photo uploading process and helps you keep your photo library organized. Say goodbye to manual photo uploads and hello to efficient photo management with
GPhoto.

## Features

- **No Duplicate Processing** - GPhoto uses a cache with keys based on hash files to ensure that the same files are not processed repeatedly, saving you time and storage.
- **No Duplicate Album Names** - GPhoto fetches album names from Google and compares them to ensure there are no duplicates, helping you keep your photo library organized and easy
  to navigate.
- **Automatic Token Refresh** - GPhoto automatically refreshes the token if an error occurs during the photo upload process, ensuring a seamless and uninterrupted uploading
  experience.
- **Secure Local Storage** - All authentication data and cache is securely stored locally to ensure your privacy and security. GPhoto does not collect any information from you.

## Installation

Please make sure you have the following requirements installed below:

- PHP 8.1 with curl, inotify, pcntl, and bcmatch extensions.

Download the latest release from the [releases page](https://github.com/OctopyID/GPhotoCLI/releases) or clone this repository.

> **Note**
>
> For now only tested on Linux environment.

## Usage

Before using this application, you need to create a Google Cloud Platform project and enable the Google Photos Library API and create
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
php gphoto auth:create foo --auth=./path/to/auth.json --host=http://localhost:3000
```

The expired token can be renewed with the following command

```bash
php gphoto auth:reload foo
```

Make sure you register http://localhost:3000 on Authorised redirect URIs and no service uses this port.

### 2. Photos

#### 2.1 Upload Single File

```bash
php gphoto upload:photo ./path/to/photo.png --auth=foo
```

or you can use ` --album` to upload to a specific album.

```bash
php gphoto upload:photo ./path/to/MyPicture.png --auth=foo --album="My Album"
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
php gphoto upload:photo ./path/to/Pictures --auth=foo
```

or you can use ` --album` to upload to a specific album.

```bash
php gphoto upload:photo ./path/to/Pictures --auth=foo --album="My Album"
```

### 3. Albums

#### 3.1 Upload Single Album

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
php gphoto upload:album ./path/to/MyAlbum --auth=foo
```

This will upload all files in the `MyAlbum` directory to Google Photos and create an album with the `MyAlbum` name.

#### 3.2 Upload Multiple Albums

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
php gphoto upload:album ./path/to/MyAlbumCollection --auth=foo --multiple
```

This will upload all files in the `MyAlbumCollection` directory to Google Photos and create an album with `MyAlbum 1` and `MyAlbum 2` names.

## Credits

- [Supian M](https://github.com/SupianIDz)
- [Octopy ID](https://github.com/OctopyID)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
