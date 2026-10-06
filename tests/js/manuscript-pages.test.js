import test from 'node:test';
import assert from 'node:assert/strict';
import { manuscriptPageLabel, visibleManuscriptPage } from '../../resources/js/manuscript-pages.js';

test('cover and Roman labels precede manuscript page one', () => {
    const mapping = { body_start: 4, preliminary_labels: ['Cover', 'i', 'ii'] };
    assert.equal(manuscriptPageLabel(mapping, 1), 'Cover');
    assert.equal(manuscriptPageLabel(mapping, 3), 'ii');
    assert.equal(manuscriptPageLabel(mapping, 4), '1');
    assert.equal(manuscriptPageLabel(mapping, 5), '2');
    assert.equal(manuscriptPageLabel(null, 3), '3');
});

test('visible page wins even when next page top is closer to viewport', () => {
    const pages = [
        { getBoundingClientRect: () => ({ top: -800, bottom: 400 }) },
        { getBoundingClientRect: () => ({ top: 420, bottom: 1620 }) },
    ];
    assert.equal(visibleManuscriptPage(pages, { top: 0, bottom: 600 }), 1);
    assert.equal(visibleManuscriptPage(pages, { top: 400, bottom: 1000 }), 2);
});
