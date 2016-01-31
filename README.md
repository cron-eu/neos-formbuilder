# FormBuilder
FormBuilder for Neos CMS.

# Installation

Add the formbuilder repository in your composer.json like:

```
    "repositories": [
        {
            "type": "git",
            "url": "git@github.com:cron-eu/neos-formbuilder.git"
        }
    ],
```

Add the dependency like:

```
    "require": {
        "cron/neos-formbuilder": "dev-master"
    },
```

Run `composer update`

# Usage

In your site package NodeTypes.yaml add the FormBuilder plugin like:

```
childNodes:
    main:
      type: 'TYPO3.Neos:ContentCollection'
      constraints:
        nodeTypes:
          'CRON.FormBuilder:Plugin': true
```

# TODO

- Add more complex validation
- Improve documentation
