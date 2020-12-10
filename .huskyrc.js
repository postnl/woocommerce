const packageName = '@postnl/checkout';

const preCommit = [
  `npm run postcss`,
  `npm update ${packageName}`,
  `npm run postinstall`,
  'git add package.json',
  'git add package-lock.json',
  'git add assets/js/postnl.js',
].join(' && ');

module.exports = {
  hooks: {
    'pre-commit': preCommit
  },
};
