const {'rules': defaultRules} = require('../presets/default');

module.exports = {
  'plugins': [
    'babel',
  ],
  'rules': {
    // Turn off the settings eslint-plugin-babel replaces.
    'new-cap'              : 'off',
    'no-unused-expressions': 'off',
    'object-curly-spacing' : 'off',
    'quotes'               : 'off',
    'semi'                 : 'off',

    'babel/new-cap'              : defaultRules['new-cap'],
    'babel/no-unused-expressions': defaultRules['no-unused-expressions'],
    'babel/object-curly-spacing' : defaultRules['object-curly-spacing'],
    'babel/quotes'               : defaultRules.quotes,
    'babel/semi'                 : defaultRules.semi,
  },
};
