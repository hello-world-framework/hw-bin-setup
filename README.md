<!-- [![Build Status](https://img.shields.io/circleci/build/gh/hello-world-framework/hw-bin-setup/main?style=flat-square)](https://circleci.com/gh/hello-world-framework/hw-bin-setup/tree/master) -->
[![Stars](https://img.shields.io/github/stars/hello-world-framework/hw-bin-setup?style=flat-square)](https://github.com/hello-world-framework/hw-bin-setup/stargazers)
[![Forks](https://img.shields.io/github/forks/hello-world-framework/hw-bin-setup?style=flat-square&color=purple)](https://github.com/hello-world-framework/hw-bin-setup/network/members)
[![Issues](https://img.shields.io/github/issues/hello-world-framework/hw-bin-setup?style=flat-square&color=blue)](https://github.com/hello-world-framework/hw-bin-setup/issues)
[![License](https://img.shields.io/github/license/hello-world-framework/hw-bin-setup?color=teal&style=flat-square)](https://github.com/hello-world-framework/hw-bin-setup/blob/master/LICENSE)

# hw-bin-setup
`hw-bin-setup.php` installs all necessary command line utilities for the HelloWorld Framework. In a more broad sense, it installs the components from `hellow-world-framework/bin` repository in a proper format to use from command line.


## Getting started

### Download `hw-bin-setup.php`
Download the `hw-bin-setup.php` in your project root. You can do so manually or by the following command:
```bash
# download installer
$ php -r "copy('https://raw.githubusercontent.com/hello-world-framework/hw-bin-setup/main/hw-bin-setup.php', 'hw-bin-setup.php');"
# verify installer
$ php -r "if(hash_file('sha384', 'hw-bin-setup.php') === 'ab3246037986292a97366c1ca05451da934cf3f0675e84782c30c0ffee8ce303c0dee5203edf50d0b39f740d7e714028') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('hw-bin-setup.php'); } echo PHP_EOL;"
```

### Installing `hello-world-framework/bin` utilities
You can download the latest `hello-world-framework/bin` utilites using the following command
```bash
$ php hw-bin-setup.php
```

Or, you may download your preferred version usng the --bin=vX.Y.Z flag as follows:
```bash
$ php hw-bin-setup.php --bin=v0.1.0 # change v0.1.0 with your preferred version
```

It will create a `hw/` directory in your project root and put the binaries in that directory.

For help you may use:
```bash
$ php hw-bin-setup.php --help # or just "-h"
```

### Removing `hw-bin-setup.php`(optional):
Now, you may want to `hw-bin-setup.php`. Once you install `hello-world-framework/bin` into your project, you no longer need it. So, you may want to do as follows:
```bash
php -r "unlink('hw-bin-setup.php');"
```

## LICENSE
To learn about the project license, visit [here](https://github.com/hello-world-framework/hw-bin-setup/blob/master/LICENSE).


## Contributing
This project is open for contributing. So, any improvements, issues or feature requests are very much welcome.
