<?php

namespace Oblik\Walker\Walker;

use Kirby\Cms\Page;
use Kirby\Data\Json;
use Oblik\Walker\TestCase;

final class WalkerTest extends TestCase
{
	public function testLink()
	{
		$result = (new Walker())->walk(new Page([
			'slug' => 'test',
			'content' => [
				'link' => null
			],
			'blueprint' => [
				'fields' => [
					'link' => [
						'type' => 'link'
					]
				]
			]
		]));

		$expected = null;

		$this->assertEquals($expected, $result);
	}

	public function testLayout()
	{
		$result = (new Walker())->walk(new Page([
			'slug' => 'test',
			'content' => [
				'text' => Json::encode([
					[
						'attrs' => [
							'anchor' => 'hello-world'
						],
						'columns' => [
							[
								'blocks' => [
									[
										'content' => [
											'level' => 'h2',
											'text' => 'Hello World!'
										],
										'id' => '65841ca6-53db-4079-a506-87c493f7808d',
										'isHidden' => false,
										'type' => 'heading'
									],
									[
										'content' => [
											'text' => '<p>Lorem Ipsum!</p>'
										],
										'id' => 'f21cb883-45d5-42ea-8292-ea6126563ad9',
										'isHidden' => false,
										'type' => 'text'
									]
								],
								'id' => '806a3187-e111-49c4-84e4-f6c37dc47600',
								'width' => '1/1'
							]
						],
						'id' => 'f7799dc1-0a65-461e-8a23-f59bdcf11ec0'
					],
					[
						'attrs' => [
							'anchor' => ''
						],
						'columns' => [
							[
								'blocks' => [
									[
										'content' => [
											'text' => 'Quote',
											'citation' => 'Author'
										],
										'id' => '7a8fb7c8-4a90-46db-9899-2a85938cc456',
										'isHidden' => false,
										'type' => 'quote'
									]
								],
								'id' => 'f1581d2a-081a-4b0b-bd1c-9fb8c908278e',
								'width' => '1/1'
							]
						],
						'id' => 'f1300976-63e9-4b3d-a452-53b57703e268'
					]
				])
			],
			'blueprint' => [
				'fields' => [
					'text' => [
						'type' => 'layout',
						'settings' => [
							'fields' => [
								'anchor' => [
									'type' => 'text'
								]
							]
						]
					]
				]
			]
		]));

		$expected = [
			'text' => [
				[
					'attrs' => [
						'anchor' => 'hello-world'
					],
					'columns' => [
						[
							'blocks' => [
								[
									'content' => [
										'level' => 'h2',
										'text' => 'Hello World!'
									],
									'id' => '65841ca6-53db-4079-a506-87c493f7808d',
									'isHidden' => false,
									'type' => 'heading'
								],
								[
									'content' => [
										'text' => '<p>Lorem Ipsum!</p>'
									],
									'id' => 'f21cb883-45d5-42ea-8292-ea6126563ad9',
									'isHidden' => false,
									'type' => 'text'
								]
							],
							'id' => '806a3187-e111-49c4-84e4-f6c37dc47600',
							'width' => '1/1'
						]
					],
					'id' => 'f7799dc1-0a65-461e-8a23-f59bdcf11ec0'
				],
				[
					'attrs' => [
						'anchor' => ''
					],
					'columns' => [
						[
							'blocks' => [
								[
									'content' => [
										'text' => 'Quote',
										'citation' => 'Author'
									],
									'id' => '7a8fb7c8-4a90-46db-9899-2a85938cc456',
									'isHidden' => false,
									'type' => 'quote'
								]
							],
							'id' => 'f1581d2a-081a-4b0b-bd1c-9fb8c908278e',
							'width' => '1/1'
						]
					],
					'id' => 'f1300976-63e9-4b3d-a452-53b57703e268'
				]
			]
		];

		$this->assertEquals($expected, $result);
	}
}
