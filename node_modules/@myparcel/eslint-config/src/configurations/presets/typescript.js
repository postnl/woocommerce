/**
 * Using this config requires you to have a tsconfig.json in your project.
 */
module.exports = {
  'parser' : '@typescript-eslint/parser',
  'plugins': [
    '@typescript-eslint',
  ],
  'parserOptions': {
    'project': './tsconfig.json',
  },
  'extends': [
    './es6',
  ],
  'rules': {
    '@typescript-eslint/consistent-type-definitions': [
      'error',
      'interface',
    ],
    '@typescript-eslint/explicit-function-return-type': 'error',
    '@typescript-eslint/no-explicit-any'              : 'error',
    '@typescript-eslint/no-non-null-assertion'        : 'off',
    '@typescript-eslint/no-use-before-define'         : 'off',
    '@typescript-eslint/no-var-requires'              : 'off',
    '@typescript-eslint/prefer-nullish-coalescing'    : 'error',
    '@typescript-eslint/prefer-optional-chain'        : 'error',
    '@typescript-eslint/unbound-method'               : 'off',
    'no-empty-function'                               : 'off',
    '@typescript-eslint/no-empty-function'            : [
      'error',
      {
        'allow': [
          'arrowFunctions',
        ],
      },
    ],
  },
};
