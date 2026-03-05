# Career Portal templates

This directory contains custom Career Portal templates. Each template lives in its own folder and follows the same structure as the default templates shipped with OpenCATS.

## Directory layout

career_portal_templates/
  <template-slug>/
    meta.json
    *.tpl

## Template folders

The folder name (<template-slug>) should be URL-friendly (lowercase letters, digits, and dashes). Use a unique name per template.

## Template files (.tpl)

Template sections are stored as .tpl files directly inside the template folder (for example: header.tpl, footer.tpl, css.tpl). The Career Portal renderer loads these files based on the section mapping in meta.json.

## meta.json

Each template must contain a meta.json file describing the template. The file includes basic metadata (for example: name/title) and an ordered list or mapping of sections that reference the corresponding .tpl files in the same folder.

## Adding or updating templates

1) Create a new folder under career_portal_templates/<template-slug>/.
2) Add meta.json.
3) Add the required .tpl files to the same folder.
4) Ensure the web server user can read these files.

## Notes

Do not place user uploads or sensitive files here. Keep templates as plain text files that can safely be served and backed up. When deploying OpenCATS, make sure this directory is included in your backup and release process if you want to preserve custom templates across updates.
