<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordTypeValue;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case
 */
class DatabaseRecordTypeValueTest extends UnitTestCase {

	/**
	 * @var DatabaseRecordTypeValue
	 */
	protected $subject;

	/**
	 * @var DatabaseConnection | ObjectProphecy
	 */
	protected $dbProphecy;

	public function setUp() {
		$this->dbProphecy = $this->prophesize(DatabaseConnection::class);
		$GLOBALS['TYPO3_DB'] = $this->dbProphecy->reveal();

		$this->subject = new DatabaseRecordTypeValue();
	}

	/**
	 * @test
	 */
	public function addDataThrowsExceptionIfTcaTypesAreEmpty() {
		$input = [
			'vanillaTableTca' => [
				'types' => [],
			],
		];

		$this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1438185331);

		$this->subject->addData($input);
	}

	/**
	 * @test
	 */
	public function addDataSetsRecordTypeValueToHistoricalOneIfTypeZeroIsNotDefined() {
		$input = [
			'vanillaTableTca' => [
				'types' => [
					'1' => 'foo',
				],
			],
		];
		$expected = $input;
		$expected['recordTypeValue'] = '1';
		$this->assertSame($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataSetsRecordTypeValueToZero() {
		$input = [
			'vanillaTableTca' => [
				'types' => [
					'0' => 'foo',
				],
			],
		];

		$expected = $input;
		$expected['recordTypeValue'] = '0';

		$this->assertSame($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataThrowsExceptionIfTypePointsToANotExistingField() {
		$input = [
			'vanillaTableTca' => [
				'ctrl' => [
					'type' => 'notExists',
				],
				'types' => [
					'0' => 'foo',
				],
			],
			'databaseRow' => [
				'uid' => 23,
			],
		];

		$this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1438183881);

		$this->subject->addData($input);
	}

	/**
	 * @test
	 */
	public function addDataSetsRecordTypeValueToValueOfDatabaseField() {
		$input = [
			'vanillaTableTca' => [
				'ctrl' => [
					'type' => 'aField',
				],
				'types' => [
					'3' => 'foo',
				],
			],
			'databaseRow' => [
				'aField' => 3,
			],
		];

		$expected = $input;
		$expected['recordTypeValue'] = '3';

		$this->assertSame($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataSetsRecordTypeValueToZeroIfValueOfDatabaseFieldIsNotDefinedInTca() {
		$input = [
			'vanillaTableTca' => [
				'ctrl' => [
					'type' => 'aField',
				],
				'types' => [
					'0' => 'foo',
				],
			],
			'databaseRow' => [
				'aField' => 3,
			],
		];

		$expected = $input;
		$expected['recordTypeValue'] = '0';

		$this->assertSame($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataSetsRecordTypeValueToZeroIfValueOfDatabaseFieldIsEmptyString() {
		$input = [
			'vanillaTableTca' => [
				'ctrl' => [
					'type' => 'aField',
				],
				'types' => [
					'0' => 'foo',
				],
			],
			'databaseRow' => [
				'aField' => '',
			],
		];

		$expected = $input;
		$expected['recordTypeValue'] = '0';

		$this->assertSame($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataThrowsExceptionIfValueTypesNotExistsAndNoFallbackExists() {
		$input = [
			'vanillaTableTca' => [
				'ctrl' => [
					'type' => 'aField',
				],
				'types' => [
					'42' => 'foo',
				],
			],
			'databaseRow' => [
				'aField' => 23,
			],
		];

		$this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1438185437);

		$this->subject->addData($input);
	}

	/**
	 * @test
	 */
	public function addDataSetsRecordTypeValueToValueOfDefaultLanguageRecordIfConfiguredAsExclude() {
		$input = [
			'vanillaTableTca' => [
				'ctrl' => [
					'languageField' => 'sys_language_uid',
					'type' => 'aField',
				],
				'columns' => [
					'aField' => [
						'l10n_mode' => 'exclude',
					],
				],
				'types' => [
					'3' => 'foo',
				],
			],
			'databaseRow' => [
				'sys_language_uid' => 2,
				'aField' => 4,
			],
			'defaultLanguageRow' => [
				'aField' => 3,
			],
		];

		$expected = $input;
		$expected['recordTypeValue'] = '3';

		$this->assertSame($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataSetsRecordTypeValueToValueOfDefaultLanguageRecordIfConfiguredAsMergeIfNotBlank() {
		$input = [
			'vanillaTableTca' => [
				'ctrl' => [
					'languageField' => 'sys_language_uid',
					'type' => 'aField',
				],
				'columns' => [
					'aField' => [
						'l10n_mode' => 'mergeIfNotBlank',
					],
				],
				'types' => [
					'3' => 'foo',
				],
			],
			'databaseRow' => [
				'sys_language_uid' => 2,
				'aField' => '',
			],
			'defaultLanguageRow' => [
				'aField' => 3,
			],
		];

		$expected = $input;
		$expected['recordTypeValue'] = '3';

		$this->assertSame($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataSetsRecordTypeValueToValueOfLocalizedRecordIfConfiguredAsMergeIfNotBlankButNotBlank() {
		$input = [
			'vanillaTableTca' => [
				'ctrl' => [
					'languageField' => 'sys_language_uid',
					'type' => 'aField',
				],
				'columns' => [
					'aField' => [
						'l10n_mode' => 'mergeIfNotBlank',
					],
				],
				'types' => [
					'3' => 'foo',
				],
			],
			'databaseRow' => [
				'sys_language_uid' => 2,
				'aField' => 3,
			],
			'defaultLanguageRow' => [
				'aField' => 4,
			],
		];

		$expected = $input;
		$expected['recordTypeValue'] = '3';

		$this->assertSame($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataThrowsExceptionForForeignTypeConfigurationNotAsSelectOrGroup() {
		$input = [
			'vanillaTableTca' => [
				'ctrl' => [
					'type' => 'localField:foreignField',
				],
				'columns' => [
					'localField' => [
						'config' => [
							'type' => 'input',
						],
					],
				],
				'types' => [
					'3' => 'foo',
				],
			],
		];

		$this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1325862240);

		$this->subject->addData($input);
	}

	/**
	 * @test
	 */
	public function addDataThrowsExceptionForForeignTypeIfPointerConfigurationHasNoTable() {
		$input = [
			'vanillaTableTca' => [
				'ctrl' => [
					'type' => 'localField:foreignField',
				],
				'columns' => [
					'localField' => [
						'config' => [
							'type' => 'select',
						],
					],
				],
				'types' => [
					'3' => 'foo',
				],
			],
			'databaseRow' => [
				'localField' => 3,
			],
		];

		$this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1438253614);

		$this->subject->addData($input);
	}

	/**
	 * @test
	 */
	public function addDataSetsTypeValueFromForeignTableRecord() {
		$input = [
			'vanillaTableTca' => [
				'ctrl' => [
					'type' => 'localField:foreignField',
				],
				'columns' => [
					'localField' => [
						'config' => [
							'type' => 'select',
							'foreign_table' => 'foreignTable',
						],
					],
				],
				'types' => [
					'3' => 'foo',
				],
			],
			'databaseRow' => [
				// Point to record 42 in foreignTable
				'localField' => 42,
			],
		];

		$foreignRecordResult = [
			'foreignField' => 3,
		];
		// Required for BackendUtility::getRecord
		$GLOBALS['TCA']['foreignTable'] = array('foo');

		$this->dbProphecy->exec_SELECTgetSingleRow('foreignField', 'foreignTable', 'uid=42')->shouldBeCalled()->willReturn($foreignRecordResult);

		$expected = $input;
		$expected['recordTypeValue'] = '3';

		$this->assertSame($expected, $this->subject->addData($input));
	}

}
