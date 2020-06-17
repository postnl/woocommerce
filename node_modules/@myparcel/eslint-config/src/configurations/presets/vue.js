const {'rules': defaultRules} = require('./default');
const {'rules': es6Rules} = require('./es6');

const overrides = {
  'generator-star-spacing': 'off',
  'id-length'             : [
    'warn',
    {
      'exceptions': [
        '$',
        '_',
        'i',
        'h',
        'e',
        'v',
      ],
    },
  ],
  'no-else-return' : 'warn',
  'no-extra-parens': [
    'warn',
    'all',
    {
      'nestedBinaryExpressions': false,
    },
  ],
  'babel/object-curly-spacing': [
    'warn',
    'always',
  ],
  'operator-linebreak': [
    'warn',
    'before',
  ],
};

module.exports = {
  'parserOptions': {
    'parser'                     : 'babel-eslint',
    'allowImportExportEverywhere': true,
  },
  'env': {
    'node': true,
  },
  'extends': [
    './es6.js',
    'plugin:vue/recommended',
  ],
  'plugins': [
    'vue',
  ],
  'rules': {
    ...overrides,

    // Add some defined types to JSDoc plugin
    'jsdoc/no-undefined-types': [
      'warn',
      {
        'definedTypes': [
          'VNode',
          'webpack',
        ],
      },
    ],

    // Vue plugin specific
    'vue/component-name-in-template-casing': [
      'warn',
      'PascalCase',
      {
        'registeredComponentsOnly': true,
      },
    ],
    'vue/html-closing-bracket-newline': [
      'warn',
      {
        'singleline': 'never',
        'multiline' : 'never',
      },
    ],
    'vue/multiline-html-element-content-newline': 'warn',

    // Uncategorized
    'vue/array-bracket-spacing'           : defaultRules['array-bracket-spacing'],
    'vue/arrow-spacing'                   : es6Rules['arrow-spacing'],
    'vue/attributes-order'                : 'warn',
    'vue/block-spacing'                   : defaultRules['block-spacing'],
    'vue/brace-style'                     : defaultRules['brace-style'],
    'vue/camelcase'                       : defaultRules.camelcase,
    'vue/comma-dangle'                    : defaultRules['comma-dangle'],
    'vue/component-definition-name-casing': 'warn',
    'vue/component-tags-order'            : [
      'warn',
      {
        'order': [
          'template',
          'script',
          'style',
        ],
      },
    ],
    'vue/dot-location'                      : defaultRules['dot-location'],
    'vue/eqeqeq'                            : defaultRules.eqeqeq,
    'vue/html-quotes'                       : 'warn',
    'vue/key-spacing'                       : defaultRules['key-spacing'],
    'vue/keyword-spacing'                   : defaultRules['keyword-spacing'],
    'vue/match-component-file-name'         : 'warn',
    'vue/max-len'                           : defaultRules['max-len'],
    'vue/no-boolean-default'                : 'warn',
    'vue/no-deprecated-scope-attribute'     : 'warn',
    'vue/no-deprecated-slot-attribute'      : 'warn',
    'vue/no-deprecated-slot-scope-attribute': 'warn',
    'vue/no-empty-pattern'                  : defaultRules['no-empty-pattern'],
    'vue/no-irregular-whitespace'           : defaultRules['no-irregular-whitespace'],
    'vue/no-reserved-component-names'       : 'error',
    'vue/no-restricted-syntax'              : defaultRules['no-restricted-syntax'],
    'vue/no-static-inline-styles'           : 'warn',
    'vue/no-unsupported-features'           : 'error',
    'vue/object-curly-spacing'              : overrides['babel/object-curly-spacing'],
    'vue/padding-line-between-blocks'       : 'warn',
    'vue/require-direct-export'             : 'warn',
    'vue/require-name-property'             : 'warn',
    'vue/script-indent'                     : 'off',
    'vue/sort-keys'                         : 'off',
    'vue/space-infix-ops'                   : defaultRules['space-infix-ops'],
    'vue/space-unary-ops'                   : defaultRules['space-unary-ops'],
    'vue/static-class-names-order'          : 'warn',
    'vue/v-on-function-call'                : 'warn',
    'vue/v-slot-style'                      : 'warn',
    'vue/valid-v-bind-sync'                 : 'error',
    'vue/valid-v-slot'                      : 'error',
  },
};
