#!/usr/bin/env php
<?php
include_once __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\ShortCircuit\Resolver;
Tap::plan(44);
$r = new Resolver;

Tap::ok($r instanceof \Gaia\ShortCircuit\Iface\Resolver, 'able to instantiate the resolver');
Tap::is($r->appdir(), '', 'by default, nothing in appdir');
$r = new Resolver('test');
Tap::is( $r->appDir(), 'test', 'arg passed to constructor sets appdir');
$r->setAppDir('test2');
Tap::is( $r->appdir(), 'test2', 'setAppDir() method changes appdir');

$r->setAppDir( __DIR__ . '/app/' );

Tap::is( $r->get('test', 'action'), __DIR__ . '/app/test.action.php', 'getting path to an action');
Tap::is( $r->get('', 'action'), __DIR__ . '/app/index.action.php', 'getting path to index action');
Tap::is( $r->get('nested', 'action'), __DIR__ . '/app/nested.action.php', 'getting path to nested index action');
Tap::is( $r->get('nested/test', 'action'), __DIR__ . '/app/nested/test.action.php', 'getting path to nested touch action');

Tap::is( $r->get('test', 'view'), __DIR__ . '/app/test.view.php', 'getting path to a view');
Tap::is( $r->get('', 'view'), __DIR__ . '/app/index.view.php', 'getting path to index view');
Tap::is( $r->get('nested', 'view'), __DIR__ . '/app/nested.view.php', 'getting path to nested index view');
Tap::is( $r->get('nested/test', 'view'), __DIR__ . '/app/nested/test.view.php', 'getting path to nested touch view');

Tap::is( $r->match('nested/test/', $args), 'nested/test', 'match test resolves correctly');
Tap::is( $r->match('nested',  $args), 'nested', 'match index resolves correctly');
Tap::is( $r->match('',  $args), 'index', 'empty match resolves to index');
Tap::is( $r->match('badpath/1/1',  $args), '', 'bad path resolves to nothing');
Tap::is( $r->match('nested/deep/test/',  $args), 'nested/deep/test', 'match traverses into a folder without an index');
Tap::is( $r->match('nested/deep/test',  $args), 'nested/deep/test', 'match finds deep match even when it is exact match');
Tap::is( $r->match('nested/deep',  $args), '', 'if it doesnt find it, fail');

$urls = array(
'/go/(id)' => 'nested/test',
'/foo/bar/(a)/test/(b)' => 'nested/deep/test',
);

$r->setUrls( $urls );
Tap::is( $r->match('/', $args), 'index', 'default url matched index');
Tap::is( $r->match('/go/123', $args), 'nested/test', 'go url matched action' );
Tap::is( $args['id'], '123', 'number extracted into the request id');
Tap::is( $r->match('/foo/bar/bazz/test/quux', $args ), 'nested/deep/test', 'deeply nested url matched action' );
Tap::is( $args, array('a'=>'bazz', 'b'=>'quux'), 'extracted the correct args');
Tap::is( $r->link('nested/test', array('id'=>123) ), '/go/123', 'pattern converted back into a url' );
Tap::is( $r->link('nested/deep/test', array('b'=>'quux', 'a'=>'bazz', 'c'=>'test')), '/foo/bar/bazz/test/quux?c=test', 'converted longer pattern with several parts into url');
Tap::is( $r->match('nested/deep/test', $args), 'nested/deep/test', 'without a pattern match, falls back on the core match method');

$patterns = array(
'/go/(id)'                  => 'nested/test',
'/gogo/(id)'                => 'nested/test',
'/numerical/(id)'           => 'id',
'/foo/bar/(a)/test/(b)'     => 'nested/deep/test',
//'/'                         => 'index',
);

$r = new Resolver( __DIR__ . '/app/', $patterns);
Tap::is( $r->match('/', $args), 'index', 'default url matched index');
Tap::is( $r->match('/go/123', $args), 'nested/test', 'go url matched action' );
Tap::is( $args['id'], '123', 'number extracted into the request id');
Tap::is( $r->match('/gogo/123', $args), 'nested/test', 'gogo url matched action' );
Tap::is( $args['id'], '123', 'number extracted into the request id');
Tap::is( $r->match('/numerical/123', $args), 'id', 'numerical url matched action' );
Tap::is( $args['id'], '123', 'number extracted into the request id');
Tap::is( $r->link('id', array('id'=>123) ), '/numerical/123', 'pattern converted back into a url' );
Tap::is( $r->match('/foo/bar/bazz/test/quux', $args ), 'nested/deep/test', 'deeply nested url matched action' );
Tap::is( $args, array('a'=>'bazz', 'b'=>'quux'), 'extracted the correct args');
Tap::is( $r->link('nested/test', array('id'=>123) ), '/go/123', 'pattern converted back into a url' );
Tap::is( $r->link('nested/deep/test', array('b'=>'quux', 'a'=>'bazz', 'c'=>'test')), '/foo/bar/bazz/test/quux?c=test', 'converted longer pattern with several parts into url');
Tap::is( $r->match('nested/deep/test/1', $args), '', 'without a pattern match fails');

$link = $r->link('nested/test', $args = array('id'=>json_encode(array('a'=>1, 'b'=>array()))));
Tap::is( $r->match(rawurldecode($link), $a), 'nested/test', 'complex encoded link with json characters converts back into a match');
Tap::cmp_ok( $a, '===', $args, 'args with json extracts properly');

$link = $r->link('nested/test', $args = array('id'=>json_decode('"\u140a\u14d5\u148d\u1585 \u14c2\u1546\u152d\u154c\u1593\u1483\u146f \u14f1\u154b\u1671\u1466\u1450\u14d0\u14c7\u1585\u1450\u1593"')));
Tap::is( $r->match(rawurldecode($link), $a), 'nested/test', 'complex encoded link with utf8 characters converts back into a match');
Tap::cmp_ok( $a, '===', $args, 'args with utf8 extracts properly');
