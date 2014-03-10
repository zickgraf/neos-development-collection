<?php
namespace TYPO3\Neos\Tests\Unit\ViewHelpers\Uri;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Fluid\Core\ViewHelper\ViewHelperVariableContainer;

/**
 * Testcase for the Link.Node view helper
 *
 */
class NodeViewHelperTest extends UnitTestCase {

	/**
	 * @var \TYPO3\Neos\ViewHelpers\Uri\NodeViewHelper
	 */
	protected $viewHelper;

	/**
	 * @var \TYPO3\Flow\Mvc\ActionRequest
	 */
	protected $request;

	/**
	 * @var \TYPO3\Flow\Mvc\Controller\ControllerContext
	 */
	protected $controllerContext;

	/**
	 * @var  \TYPO3\TypoScript\Core\Runtime
	 */
	protected $tsRuntime;

	/**
	 * @var \TYPO3\Flow\Mvc\Routing\UriBuilder
	 */
	protected $uriBuilderMock;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Model\Workspace
	 */
	protected $mockLiveWorkspace;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Service\Context
	 */
	protected $mockLiveContext;

	/**
	 * Set up common mocks and object under test
	 */
	public function setUp() {
		$this->uriBuilderMock = $this->getMock('TYPO3\Flow\Mvc\Routing\UriBuilder', array('build', 'setCreateAbsoluteUri', 'setArguments'));
		$this->uriBuilderMock->expects($this->any())->method('build')->will($this->returnValue('dummy/final/url'));
		$this->uriBuilderMock->expects($this->any())->method('setCreateAbsoluteUri')->will($this->returnSelf());
		$this->uriBuilderMock->expects($this->any())->method('setArguments')->will($this->returnSelf());
		$parentHttpRequest = $this->getMockBuilder('TYPO3\Flow\Http\Request')->disableOriginalConstructor()->getMock();
		$this->request = $this->getMock('TYPO3\Flow\Mvc\ActionRequest', array('dummy'), array($parentHttpRequest));
		$this->request->expects($this->any())->method('getMainRequest')->will($this->returnValue($this->request));
		$this->controllerContext = $this->getMock('TYPO3\Flow\Mvc\Controller\ControllerContext', array(), array(), '', FALSE);
		$this->controllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
		$this->controllerContext->expects($this->any())->method('getUriBuilder')->will($this->returnValue($this->uriBuilderMock));
		$this->viewHelper = $this->getAccessibleMock('TYPO3\Neos\ViewHelpers\Uri\NodeViewHelper', array('dummy'));

		$this->inject($this->viewHelper, 'controllerContext', $this->controllerContext);

		$this->mockLiveWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();
		$this->mockLiveWorkspace->expects($this->any())->method('getName')->will($this->returnValue('live'));

		$this->mockLiveContext = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Service\Context')->disableOriginalConstructor()->getMock();
		$this->mockLiveContext->expects($this->any())->method('getWorkspace')->will($this->returnValue($this->mockLiveWorkspace));

		$this->tsRuntime = $this->getAccessibleMock('TYPO3\TypoScript\Core\Runtime', array('getCurrentContext'), array(), '', FALSE);
		$typoScriptObject = $this->getAccessibleMock('TYPO3\TypoScript\TypoScriptObjects\TemplateImplementation', array('getTsRuntime'), array(), '', FALSE);
		$typoScriptObject->expects($this->any())->method('getTsRuntime')->will($this->returnValue($this->tsRuntime));
		$mockView = $this->getAccessibleMock('TYPO3\TypoScript\TypoScriptObjects\Helpers\FluidView', array(), array(), '', FALSE);
		$mockView->expects($this->any())->method('getTypoScriptObject')->will($this->returnValue($typoScriptObject));
		$viewHelperVariableContainer = new ViewHelperVariableContainer();
		$viewHelperVariableContainer->setView($mockView);
		$this->inject($this->viewHelper, 'viewHelperVariableContainer', $viewHelperVariableContainer);
	}

	/**
	 * @test
	 */
	public function viewHelperConvertsANodeToItsIdentifierWhenInLiveWorkspace() {
		$nodeIdentifier = '15079bba-a755-4c86-8770-9a17e5c058bb';
		$node = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');

		$node->expects($this->atLeastOnce())->method('getContext')->will($this->returnValue($this->mockLiveContext));
		$node->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue($nodeIdentifier));

		$this->uriBuilderMock->expects($this->atLeastOnce())->method('build')->with(array(
			'node' => $nodeIdentifier,
			'@action' => 'show',
			'@controller' => 'frontend\node',
			'@package' => 'typo3.neos'
		));

		$this->viewHelper->render($node);
	}

	/**
	 * @test
	 */
	public function viewHelperConvertsANodeToItsContextPathWhenNotInLiveWorkspace() {
		$nodeContextPath = 'some/context/path@workspace-name';
		$node = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');

		$mockWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();
		$mockWorkspace->expects($this->atLeastOnce())->method('getName')->will($this->returnValue('not-live'));

		$mockContext = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Service\Context')->disableOriginalConstructor()->getMock();
		$mockContext->expects($this->atLeastOnce())->method('getWorkspace')->will($this->returnValue($mockWorkspace));

		$node->expects($this->atLeastOnce())->method('getContext')->will($this->returnValue($mockContext));
		$node->expects($this->atLeastOnce())->method('getContextPath')->will($this->returnValue($nodeContextPath));

		$this->uriBuilderMock->expects($this->atLeastOnce())->method('build')->with(array(
			'node' => $nodeContextPath,
			'@action' => 'show',
			'@controller' => 'frontend\node',
			'@package' => 'typo3.neos'
		));

		$this->viewHelper->render($node);
	}

	/**
	 * @test
	 */
	public function viewHelperUsesNodeInstanceWhenGiven() {
		$nodeIdentifier = '15079bba-a755-4c86-8770-9a17e5c058bb';
		$node = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');

		$node->expects($this->atLeastOnce())->method('getContext')->will($this->returnValue($this->mockLiveContext));
		$node->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue($nodeIdentifier));

		$this->uriBuilderMock->expects($this->atLeastOnce())->method('build')->with(array(
			'node' => $nodeIdentifier,
			'@action' => 'show',
			'@controller' => 'frontend\node',
			'@package' => 'typo3.neos'
		));

		$this->viewHelper->render($node);
	}

	/**
	 * @test
	 */
	public function viewHelperUsesDocumentNodeFromContextIfNoNodeGiven() {
		$documentNodeIdentifier = '15079bba-a755-4c86-8770-9a17e5c058bb';
		$documentNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');

		$documentNode->expects($this->atLeastOnce())->method('getContext')->will($this->returnValue($this->mockLiveContext));
		$documentNode->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue($documentNodeIdentifier));

		$this->tsRuntime->expects($this->atLeastOnce())->method('getCurrentContext')->will($this->returnValue(array('documentNode' => $documentNode)));

		$this->uriBuilderMock->expects($this->atLeastOnce())->method('build')->with(array(
			'node' => $documentNodeIdentifier,
			'@action' => 'show',
			'@controller' => 'frontend\node',
			'@package' => 'typo3.neos'
		));

		$this->viewHelper->render(NULL);
	}

	/**
	 * @test
	 */
	public function viewHelperFetchesNodeWithRelativePathFromDocumentNodeInContextWhenNodeIsGivenAsRelativePathString() {
		$relativeNodeIdentifier = '15079bba-a755-4c86-8770-9a17e5c058bb';
		$documentNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');
		$relativeNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');

		$relativeNode->expects($this->atLeastOnce())->method('getContext')->will($this->returnValue($this->mockLiveContext));
		$relativeNode->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue($relativeNodeIdentifier));

		$this->tsRuntime->expects($this->atLeastOnce())->method('getCurrentContext')->will($this->returnValue(array('documentNode' => $documentNode)));

		$documentNode->expects($this->any())->method('getNode')->with('some/relative/path')->will($this->returnValue($relativeNode));

		$this->uriBuilderMock->expects($this->atLeastOnce())->method('build')->with(array(
			'node' => $relativeNodeIdentifier,
			'@action' => 'show',
			'@controller' => 'frontend\node',
			'@package' => 'typo3.neos'
		));

		$this->viewHelper->render('some/relative/path');
	}

	/**
	 * @test
	 */
	public function viewHelperFetchesNodeWithRelativePathFromDocumentNodeSiteNodeWhenNodeIsGivenAsStringWithTilde() {
		$relativeNodeIdentifier = '15079bba-a755-4c86-8770-9a17e5c058bb';
		$currentSiteNodeMock = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');
		$documentNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');
		$relativeNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');

		$relativeNode->expects($this->atLeastOnce())->method('getContext')->will($this->returnValue($this->mockLiveContext));
		$relativeNode->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue($relativeNodeIdentifier));

		$this->tsRuntime->expects($this->atLeastOnce())->method('getCurrentContext')->will($this->returnValue(array('documentNode' => $documentNode)));

		$currentSiteNodeMock->expects($this->atLeastOnce())->method('getNode')->with('some/site/path')->will($this->returnValue($relativeNode));
		$contentContext = $this->getMock('TYPO3\Neos\Domain\Service\ContentContext', array(), array(), '', FALSE);
		$contentContext->expects($this->any())->method('getCurrentSiteNode')->will($this->returnValue($currentSiteNodeMock));

		$documentNode->expects($this->any())->method('getContext')->will($this->returnValue($contentContext));

		$this->uriBuilderMock->expects($this->atLeastOnce())->method('build')->with(array(
			'node' => $relativeNodeIdentifier,
			'@action' => 'show',
			'@controller' => 'frontend\node',
			'@package' => 'typo3.neos'
		));


		$this->viewHelper->render('~/some/site/path');
	}

	/**
	 * @test
	 */
	public function viewHelperRespectsFormatParameter() {
		$nodeIdentifier = '15079bba-a755-4c86-8770-9a17e5c058bb';
		$node = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');

		$node->expects($this->atLeastOnce())->method('getContext')->will($this->returnValue($this->mockLiveContext));
		$node->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue($nodeIdentifier));

		$this->uriBuilderMock->expects($this->atLeastOnce())->method('build')->with(array(
			'node' => $nodeIdentifier,
			'@action' => 'show',
			'@controller' => 'frontend\node',
			'@package' => 'typo3.neos',
			'@format' => 'someformat'
		));

		$this->viewHelper->render($node, 'someFormat');
	}

	/**
	 * @test
	 */
	public function viewHelperRespectsAbsoluteParameter() {
		$nodeIdentifier = '15079bba-a755-4c86-8770-9a17e5c058bb';
		$node = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');

		$node->expects($this->atLeastOnce())->method('getContext')->will($this->returnValue($this->mockLiveContext));
		$node->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue($nodeIdentifier));

		$this->uriBuilderMock->expects($this->atLeastOnce())->method('setCreateAbsoluteUri')->with(TRUE);
		$this->viewHelper->render($node, NULL, TRUE);
	}

	/**
	 * @test
	 */
	public function viewHelperRespectsBaseNodeNameParameter() {
		$someNodeIdentifier = '15079bba-a755-4c86-8770-9a17e5c058bb';
		$someNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');

		$someNode->expects($this->atLeastOnce())->method('getContext')->will($this->returnValue($this->mockLiveContext));
		$someNode->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue($someNodeIdentifier));

		$this->tsRuntime->expects($this->atLeastOnce())->method('getCurrentContext')->will($this->returnValue(array('someBaseNode' => $someNode)));

		$this->uriBuilderMock->expects($this->atLeastOnce())->method('build')->with(array(
			'node' => $someNodeIdentifier,
			'@action' => 'show',
			'@controller' => 'frontend\node',
			'@package' => 'typo3.neos'
		));

		$this->viewHelper->render(NULL, NULL, FALSE, 'someBaseNode');
	}

	/**
	 * @test
	 */
	public function viewHelperRespectsArgumentsParameter() {
		$arguments = array('foo' => 'bar', 'baz' => 'Foos');
		$nodeIdentifier = '15079bba-a755-4c86-8770-9a17e5c058bb';
		$node = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');

		$node->expects($this->atLeastOnce())->method('getContext')->will($this->returnValue($this->mockLiveContext));
		$node->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue($nodeIdentifier));

		$this->uriBuilderMock->expects($this->atLeastOnce())->method('setArguments')->with($arguments);

		$this->viewHelper->render($node, NULL, FALSE, 'documentNode', $arguments);
	}

}
