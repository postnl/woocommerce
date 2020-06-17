# MyParcel ESLint
This package contains multiple ESLint presets for different types of projects. Additional information, documentation and guides on ESLint can be found on https://eslint.org/ 

## Usage
Install the package via npm:
```
$ npm i -D @myparcel/eslint-config
```

Create an [ESLint config file], if you haven't already, and add the following: (JavaScript example)
```js
module.exports = {
  extends: [
    // Base config, same as '@myparcel/eslint-config/preset-default',
    '@myparcel/eslint-config',
  ],
};
```
Or to use another preset with an extra plugin:    
```js
module.exports = {
  extends: [
    // Vue.js config
    '@myparcel/eslint-config/preset-vue',  
    '@myparcel/eslint-config/plugin-you-dont-need-momentjs',  
  ],
};
```    
Be sure you only use one preset at a time. You can use multiple plugins, though.

## Presets
These are the presets for various types of projects. They are named `plugin-<name>.js`. All presets (eventually) extend the base config `preset-default.js`. 

Try to to create a new preset (if possible) for your project instead of using the base and adding/overriding tons of rules so it can be reused. The base config enforces a lot of basic syntax rules like whitespace and punctuation. Please avoid overriding these rules where possible!

If there's anything missing ([globals], [environments], [rules] etc.) please add them in this repository and create a pull request instead of adding them in your project configuration. If it's truly project specific you don't have to do this.

### Base config 
> `@myparcel/eslint-config(/preset-default)`

This config contains the bare bones setup. It extends plugin configs that should be used in every project and contains all base rules. All other presets should extend this one.

### ES5
> `@myparcel/eslint-config/preset-es5`

This config is made for any project using ES5 JavaScript. The environment `es5` is set and it extends the base config.

### ES6
> `@myparcel/eslint-config/preset-es6`

This config is made as a base for any project using modern JavaScript. It's meant to always use the latest ECMAScript version. The environment `es6` is set and it extends the base config.

### Meteor 
> `@myparcel/eslint-config/preset-meteor`

This config is made for [Meteor] projects. In addition to the base config it extends `eslint:recommended` and `plugin:meteor/recommended`. The needed [environments] are already set and it adds some more [globals] from Meteor modules. 

### Vue
> `@myparcel/eslint-config/preset-vue`

This config is made for [Vue.js] projects. In addition to the base config it extends `plugin:vue/recommended`. It supports linting `.vue` files by using [eslint-plugin-vue].

### TypeScript
> `@myparcel/eslint-config/preset-typescript`

This config is made for [TypeScript] projects. You need to have a `tsconfig.json` in your project root to use this preset. Uses [typescript-eslint/eslint-plugin] and [typescript-eslint/eslint-parser].

## Plugin configs
These configs are meant to be extended by other configs to add functionality, not to be used on their own. They are named `plugin-<name>.js`.

### JSDoc
> `@myparcel/eslint-config/plugin-jsdoc`

Extended by the base config. Contains [eslint-plugin-jsdoc] and applies its custom rules for [JSDoc] comments.

### Jest
> `@myparcel/eslint-config/plugin-jest`

Extend this config in any project using [Jest]. Contains rules from [eslint-plugin-jest].

### You Don't Need MomentJS
> `@myparcel/eslint-config/plugin-you-dont-need-momentjs`

Contains [eslint-plugin-you-dont-need-momentjs] and applies its custom rules.

[ESLint config file]: https://eslint.org/docs/user-guide/configuring
[environments]: https://eslint.org/docs/user-guide/configuring#specifying-environments
[globals]: https://eslint.org/docs/user-guide/configuring#specifying-globals
[rules]: https://eslint.org/docs/rules/
[Meteor]: https://www.meteor.com/
[Vue.js]: https://vuejs.org/
[eslint-plugin-vue]: https://github.com/vuejs/eslint-plugin-vue
[TypeScript]: https://www.typescriptlang.org/
[typescript-eslint]: https://github.com/typescript-eslint/typescript-eslint
[typescript-eslint/eslint-plugin]: https://github.com/typescript-eslint/typescript-eslint/tree/master/packages/eslint-plugin
[typescript-eslint/eslint-parser]: https://github.com/typescript-eslint/typescript-eslint/tree/master/packages/parser
[JSDoc]: https://devdocs.io/jsdoc/
[eslint-plugin-jsdoc]: https://www.npmjs.com/package/eslint-plugin-jsdoc
[Jest]: https://jestjs.io/
[eslint-plugin-jest]: https://www.npmjs.com/package/eslint-plugin-jest
[eslint-plugin-you-dont-need-momentjs]: https://www.npmjs.com/package/eslint-plugin-you-dont-need-momentjs
