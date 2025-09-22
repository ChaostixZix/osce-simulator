import prettier from 'eslint-config-prettier';
import react from 'eslint-plugin-react';
import reactHooks from 'eslint-plugin-react-hooks';
import jsx from 'eslint-plugin-jsx-a11y';
import typescript from '@typescript-eslint/eslint-plugin';
import parser from '@typescript-eslint/parser';

export default [
    {
        ignores: ['vendor/**', 'node_modules/**', 'public/**', 'bootstrap/ssr/**', 'tailwind.config.js', 'resources/js/components/ui/*'],
    },
    {
        files: ['**/*.{js,jsx,ts,tsx}'],
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            parser: parser,
            parserOptions: {
                ecmaFeatures: {
                    jsx: true,
                },
                project: './tsconfig.json',
            },
        },
        plugins: {
            react,
            'react-hooks': reactHooks,
            'jsx-a11y': jsx,
            '@typescript-eslint': typescript,
        },
        settings: {
            react: {
                version: 'detect',
            },
        },
        rules: {
            // React rules
            'react/jsx-uses-react': 'off', // Not needed with React 17+
            'react/react-in-jsx-scope': 'off', // Not needed with React 17+
            'react/prop-types': 'off', // Using TypeScript instead
            'react/jsx-no-target-blank': 'error',
            'react/jsx-key': 'error',
            'react/no-array-index-key': 'warn',
            'react/no-unescaped-entities': 'warn',

            // React Hooks rules
            'react-hooks/rules-of-hooks': 'error',
            'react-hooks/exhaustive-deps': 'warn',

            // TypeScript rules
            '@typescript-eslint/no-explicit-any': 'off',
            '@typescript-eslint/no-unused-vars': ['warn', { argsIgnorePattern: '^_' }],
            '@typescript-eslint/explicit-function-return-type': 'off',
            '@typescript-eslint/explicit-module-boundary-types': 'off',

            // General rules
            'no-console': 'warn',
            'no-debugger': 'error',
            'prefer-const': 'error',
            'no-var': 'error',
        },
    },
    prettier,
];
