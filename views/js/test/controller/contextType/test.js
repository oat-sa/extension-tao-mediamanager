define(['taoMediaManager/controller/contextType'], function (getContextType) {
    'use strict';

    QUnit.module('taoMediaManager/controller/contextType');

    QUnit.test('uses first value when context is an array', function (assert) {
        assert.strictEqual(getContextType({ context: ['instance', 'resource'] }), 'instance');
    });

    QUnit.test('falls back to type when context is not an array', function (assert) {
        assert.strictEqual(getContextType({ context: { id: 1 }, type: 'class' }), 'class');
    });

    QUnit.test('falls back to context when it is a scalar', function (assert) {
        assert.strictEqual(getContextType({ context: 'class' }), 'class');
    });

    QUnit.test('infers instance when uri exists', function (assert) {
        assert.strictEqual(getContextType({ uri: 'http://example.com/resource/1' }), 'instance');
    });

    QUnit.test('infers class when no fallback values are available', function (assert) {
        assert.strictEqual(getContextType({}), 'class');
    });
});
