module.exports = {
  'parser'       : 'babel-eslint',
  'parserOptions': {
    'sourceType'                 : 'module',
    'allowImportExportEverywhere': true,
  },
  'env': {
    'es2020': true,
  },
  'extends': [
    './default.js',
    '../plugins/babel.js',
  ],
  'rules': {
    // Default overrides
    'func-names': [
      'warn',
      'as-needed',
    ],
    'no-magic-numbers': [
      'warn',
      {
        'ignore': [
          -1,
          0,
          1,
          100,
        ],
        'detectObjects': true,
        'enforceConst' : true,
      },
    ],

    // ES6+ only
    'arrow-body-style': 'off',
    'arrow-parens'    : 'warn',
    'arrow-spacing'   : [
      'warn',
      {
        'before': true,
        'after' : true,
      },
    ],
    'constructor-super'        : 'off',
    'generator-star-spacing'   : 'off',
    'no-async-promise-executor': 'warn',
    'no-await-in-loop'         : 'off',
    'no-class-assign'          : 'off',
    'no-confusing-arrow'       : 'warn',
    'no-const-assign'          : 'warn',
    'no-dupe-class-members'    : 'warn',
    'no-duplicate-imports'     : 'warn',
    'no-new-symbol'            : 'off',
    'no-restricted-imports'    : 'off',
    'no-return-await'          : 'warn',
    'no-this-before-super'     : 'off',
    'no-useless-computed-key'  : 'off',
    'no-useless-constructor'   : 'off',
    'no-useless-rename'        : 'warn',
    'no-var'                   : 'warn',
    'object-shorthand'         : 'off',
    'prefer-arrow-callback'    : 'off',
    'prefer-const'             : 'warn',
    'prefer-destructuring'     : [
      'warn',
      {
        'VariableDeclarator': {
          'array' : false,
          'object': true,
        },
        'AssignmentExpression': {
          'array' : false,
          'object': false,
        },
      },
    ],
    'prefer-numeric-literals': 'off',
    'prefer-rest-params'     : 'off',
    'prefer-spread'          : 'warn',
    'prefer-template'        : 'warn',
    'require-await'          : 'warn',
    'require-yield'          : 'off',
    'rest-spread-spacing'    : 'off',
    'sort-imports'           : 'warn',
    'symbol-description'     : 'off',
    'template-curly-spacing' : 'warn',
    'yield-star-spacing'     : 'off',
  },
};
