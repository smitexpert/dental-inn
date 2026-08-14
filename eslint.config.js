import js from '@eslint/js'
import vue from 'eslint-plugin-vue'
import jsdoc from 'eslint-plugin-jsdoc'

export default [
  js.configs.recommended,
  ...vue.configs['flat/recommended'],
  {
    files: ['**/*.{js,vue}'],
    languageOptions: {
      ecmaVersion: 2024,
      sourceType: 'module',
      globals: {
        window: 'readonly',
        document: 'readonly',
        localStorage: 'readonly',
        navigator: 'readonly',
        confirm: 'readonly',
        alert: 'readonly',
        FormData: 'readonly',
        URL: 'readonly',
        setTimeout: 'readonly',
        clearTimeout: 'readonly',
        setInterval: 'readonly',
        clearInterval: 'readonly',
        import: 'readonly',
        export: 'readonly',
      },
    },
    rules: {
      'vue/multi-word-component-names': 'off',
      'no-unused-vars': ['warn', { argsIgnorePattern: '^_' }],
    },
  },
  {
    files: ['resources/js/types/**/*.js'],
    plugins: { jsdoc },
    rules: {
      'jsdoc/no-undefined-types': 'off',
      'jsdoc/require-jsdoc': 'off',
    },
  },
  {
    ignores: [
      'public/build/**',
      'node_modules/**',
      'vendor/**',
      'storage/**',
      'bootstrap/cache/**',
      'resources/js/test-setup.js',
    ],
  },
]
