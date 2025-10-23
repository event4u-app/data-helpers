// @ts-check
import { defineConfig } from 'astro/config';
import starlight from '@astrojs/starlight';
import starlightThemeRapide from 'starlight-theme-rapide';

// https://astro.build/config
export default defineConfig({
	site: 'https://event4u-app.github.io',
	base: '/data-helpers',
	trailingSlash: 'always',
	integrations: [
		starlight({
			title: 'event4u Data Helpers',
			description: 'Framework-agnostic PHP library for data manipulation, transformation, and validation',
			social: [
				{
					icon: 'heart',
					label: 'Sponsor',
					href: 'https://event4u-app.github.io/data-helpers/getting-started/sponsoring/',
				},
				{
					icon: 'github',
					label: 'GitHub',
					href: 'https://github.com/event4u-app/data-helpers',
				},
			],
			editLink: {
				baseUrl: 'https://github.com/event4u-app/data-helpers/edit/main/starlight/',
			},
			customCss: [
				'./src/styles/custom.css',
			],
			plugins: [
				starlightThemeRapide(),
			],
			sidebar: [
				{
					label: 'Getting Started',
                    collapsed: false,
					items: [
						{ label: 'Introduction', slug: 'getting-started/introduction' },
                        { label: 'Support Us', slug: 'getting-started/sponsoring' },
						{ label: 'Installation', slug: 'getting-started/installation' },
						{ label: 'Requirements', slug: 'getting-started/requirements' },
						{ label: 'Quick Start', slug: 'getting-started/quick-start' },
						{ label: 'Configuration', slug: 'getting-started/configuration' },
					],
				},
				{
					label: 'Core Concepts',
                    collapsed: true,
					items: [
						{ label: 'Dot-Notation Paths', slug: 'core-concepts/dot-notation' },
						{ label: 'Wildcards', slug: 'core-concepts/wildcards' },
						{ label: 'Data Types Support', slug: 'core-concepts/data-types' },
						{ label: 'Framework Detection', slug: 'core-concepts/framework-detection' },
						{ label: 'Performance & Caching', slug: 'core-concepts/performance' },
					],
				},
                {
                    label: 'Main Classes',
                    collapsed: true,
                    items: [
                        { label: 'Overview', slug: 'main-classes/overview' },
                        { label: 'DataAccessor', slug: 'main-classes/data-accessor' },
                        { label: 'DataMutator', slug: 'main-classes/data-mutator' },
                        { label: 'DataFilter', slug: 'main-classes/data-filter' },
                    ],
                },
				{
					label: 'DataMapper',
					collapsed: false,
					items: [
						{ label: 'Introduction', slug: 'main-classes/data-mapper' },
						{ label: 'Template Expressions', slug: 'advanced/template-expressions' },
						{ label: 'Query Builder', slug: 'advanced/query-builder' },
						{ label: 'GROUP BY Operator', slug: 'advanced/group-by' },
						{ label: 'Pipelines', slug: 'advanced/pipelines' },
						{ label: 'Callback Filters', slug: 'advanced/callback-filters' },
						{ label: 'Reverse Mapping', slug: 'advanced/reverse-mapping' },
					],
				},
				{
					label: 'SimpleDTO',
                    collapsed: false,
					items: [
						{ label: 'Introduction', slug: 'simple-dto/introduction' },
						{ label: 'Creating DTOs', slug: 'simple-dto/creating-dtos' },
						{ label: 'Type Casting', slug: 'simple-dto/type-casting' },
						{ label: 'Validation', slug: 'simple-dto/validation' },
						{ label: 'Property Mapping', slug: 'simple-dto/property-mapping' },
						{ label: 'Serialization', slug: 'simple-dto/serialization' },
						{ label: 'Conditional Properties', slug: 'simple-dto/conditional-properties' },
						{ label: 'Lazy Properties', slug: 'simple-dto/lazy-properties' },
						{ label: 'Computed Properties', slug: 'simple-dto/computed-properties' },
						{ label: 'Collections', slug: 'simple-dto/collections' },
						{ label: 'Nested DTOs', slug: 'simple-dto/nested-dtos' },
						{ label: 'Security & Visibility', slug: 'simple-dto/security-visibility' },
						{ label: 'TypeScript Generation', slug: 'simple-dto/typescript-generation' },
						{ label: 'IDE Support', slug: 'simple-dto/ide-support' },
					],
				},
				{
					label: 'Advanced Features',
					collapsed: true,
					items: [
						{ label: 'Custom Casts', slug: 'advanced/custom-casts' },
						{ label: 'Custom Validation', slug: 'advanced/custom-validation' },
						{ label: 'Custom Attributes', slug: 'advanced/custom-attributes' },
						{ label: 'Hooks & Events', slug: 'advanced/hooks-events' },
						{ label: 'Extending DTOs', slug: 'advanced/extending-dtos' },
					],
				},
				{
					label: 'Attributes',
					collapsed: true,
					items: [
						{ label: 'Overview', slug: 'attributes/overview' },
						{ label: 'Validation Attributes', slug: 'attributes/validation' },
						{ label: 'Casting Attributes', slug: 'attributes/casting' },
						{ label: 'Mapping Attributes', slug: 'attributes/mapping' },
						{ label: 'Visibility Attributes', slug: 'attributes/visibility' },
						{ label: 'Conditional Attributes', slug: 'attributes/conditional' },
					],
				},
                {
                    label: 'Helpers',
                    collapsed: true,
                    items: [
                        { label: 'CallbackHelper', slug: 'helpers/callback-helper' },
                        { label: 'EnvHelper', slug: 'helpers/env-helper' },
                        { label: 'MathHelper', slug: 'helpers/math-helper' },
                        { label: 'ConfigHelper', slug: 'helpers/config-helper' },
                        { label: 'DotPathHelper', slug: 'helpers/dot-path-helper' },
                        { label: 'ObjectHelper', slug: 'helpers/object-helper' },
                    ],
                },
				{
					label: 'Framework Integration',
					collapsed: true,
					items: [
						{ label: 'Overview', slug: 'framework-integration/overview' },
						{ label: 'Laravel', slug: 'framework-integration/laravel' },
						{ label: 'Symfony', slug: 'framework-integration/symfony' },
						{ label: 'Doctrine', slug: 'framework-integration/doctrine' },
						{ label: 'Plain PHP', slug: 'framework-integration/plain-php' },
						{ label: 'Artisan Commands', slug: 'framework-integration/artisan-commands' },
						{ label: 'Console Commands', slug: 'framework-integration/console-commands' },
					],
				},
				{
					label: 'Performance',
					collapsed: true,
					items: [
						{ label: 'Benchmarks', slug: 'performance/benchmarks' },
						{ label: 'Running Benchmarks', slug: 'performance/running-benchmarks' },
						{ label: 'Optimization Tips', slug: 'performance/optimization' },
						{ label: 'Comparison', slug: 'performance/comparison' },
					],
				},
				{
					label: 'Examples',
					collapsed: true,
					items: [
						{ label: 'API Integration', slug: 'examples/api-integration' },
						{ label: 'Form Processing', slug: 'examples/form-processing' },
						{ label: 'Database Operations', slug: 'examples/database-operations' },
						{ label: 'File Upload', slug: 'examples/file-upload' },
						{ label: 'Real-World Apps', slug: 'examples/real-world' },
					],
				},
				{
					label: 'API Reference',
					collapsed: true,
					items: [
						{ label: 'DataAccessor', slug: 'api/data-accessor' },
						{ label: 'DataMutator', slug: 'api/data-mutator' },
						{ label: 'DataMapper', slug: 'api/data-mapper' },
						{ label: 'DataFilter', slug: 'api/data-filter' },
						{ label: 'SimpleDTO', slug: 'api/simple-dto' },
						{ label: 'Helpers', slug: 'api/helpers' },
						{ label: 'Attributes', slug: 'api/attributes' },
						{ label: 'Casts', slug: 'api/casts' },
					],
				},
				{
				label: 'Guides',
				collapsed: true,
				items: [
					{ label: 'Architecture', slug: 'guides/architecture' },
					{ label: 'Development Setup', slug: 'guides/development-setup' },
					{ label: 'Taskfile Reference', slug: 'guides/taskfile-reference' },
					{ label: 'Test Matrix', slug: 'guides/test-matrix' },
					{ label: 'Package Verification', slug: 'guides/package-verification' },
					{ label: 'Contributing Guide', slug: 'guides/contributing' },
					{ label: 'Fork & Pull Request', slug: 'guides/fork-and-pull-request' },
					{ label: 'Migration from Spatie', slug: 'guides/migration-from-spatie' },
				],
			},
			{
				label: 'Testing',
				collapsed: true,
				items: [
					{ label: 'Testing DTOs', slug: 'testing/testing-dtos' },
				],
			},
			{
                label: 'Troubleshooting',
                collapsed: true,
                items: [
                    { label: 'Common Issues', slug: 'troubleshooting/common-issues' },
                ],
			},
			],
		}),
	],
});
