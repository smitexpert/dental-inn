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
        prompt: 'readonly',
        FormData: 'readonly',
        URL: 'readonly',
        URLSearchParams: 'readonly',
        fetch: 'readonly',
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
      'no-restricted-imports': ['error', {
        paths: [
          { name: 'primevue', message: 'PrimeVue is removed. Use the DaisyUI kit in resources/js/Components/ui/.' },
          { name: '@primevue/themes', message: 'PrimeVue themes are removed.' },
          { name: '@primeuix/themes', message: 'PrimeVue themes are removed.' },
        ],
        patterns: [
          { group: ['primevue/*'], message: 'PrimeVue is removed. Use the DaisyUI kit.' },
          { group: ['@primevue/*'], message: 'PrimeVue is removed.' },
          { group: ['@primeuix/*'], message: 'PrimeVue themes are removed.' },
        ],
      }],
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
