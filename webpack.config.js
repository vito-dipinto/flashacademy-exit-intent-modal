// webpack.config.js
const path = require('path');
const defaultConfig = require('@wordpress/scripts/config/webpack.config');

module.exports = {
  ...defaultConfig,

  entry: () => {
    const baseEntry =
      typeof defaultConfig.entry === 'function'
        ? defaultConfig.entry()
        : defaultConfig.entry;

    return {
      ...baseEntry,
      'faeim-modal-frontend': path.resolve(
        __dirname,
        'src/faeim-modal-frontend/index.js'
      ),
    };
  },

  output: {
    ...defaultConfig.output,
    filename: (pathData) => {
      if (pathData.chunk.name === 'faeim-modal-frontend') {
        return 'faeim-modal-frontend/index.js';
      }
      return defaultConfig.output.filename;
    },
  },
};
