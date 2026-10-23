import prettier from 'eslint-config-prettier';
import react from 'eslint-plugin-react';
import reactHooks from 'eslint-plugin-react-hooks';
import jsx from 'eslint-plugin-jsx-a11y';
import typescript from '@typescript-eslint/eslint-plugin';
import parser from '@typescript-eslint/parser';

export default [
    { ignores: ['vendor/**', 'node_modules/**', 'public/**', 'bootstrap/**', 'webapp/**', 'tailwind.config.js', 'resources/js/components/ui/**'] },
    {
        files: ['**/*.{js,jsx,ts,tsx}'],
        languageOptions: { ecmaVersion: 'latest', sourceType: 'module', parser, parserOptions: { ecmaFeatures: { jsx: true } } },
        plugins: { react, 'react-hooks': reactHooks, 'jsx-a11y': jsx, '@typescript-eslint': typescript },
        settings: { react: { version: 'detect' } },
        rules: {
            'react/jsx-uses-react': 'off',
            'react/react-in-jsx-scope': 'off',
            'react/prop-types': 'off',
            'react/jsx-no-target-blank': 'error',
            'react/jsx-key': 'error',
            'react/no-array-index-key': 'warn',
            'react/no-unescaped-entities': 'warn',
            'react-hooks/rules-of-hooks': 'error',
            'react-hooks/exhaustive-deps': 'warn',
            '@typescript-eslint/no-explicit-any': 'off',
            '@typescript-eslint/no-unused-vars': ['warn', { argsIgnorePattern: '^_' }],
            '@typescript-eslint/explicit-function-return-type': 'off',
            '@typescript-eslint/explicit-module-boundary-types': 'off',
            'no-console': 'warn',
            'no-debugger': 'error',
            'prefer-const': 'error',
            'no-var': 'error',
        },
    },
    {
        files: ['resources/js/pages/Guest/*.jsx', 'resources/js/components/guest/*.jsx'],
        rules: {
            'jsx-a11y/label-has-associated-control': 'error',
            'jsx-a11y/alt-text': 'error',
            'jsx-a11y/anchor-is-valid': ['error', { aspects: ['noHref', 'invalidHref', 'preferButton'] }],
        },
    },
    prettier,
];
