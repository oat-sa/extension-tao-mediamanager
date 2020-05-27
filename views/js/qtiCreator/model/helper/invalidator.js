define(['lodash'], function(_) {


    const invalidator = {
        completelyValid(element) {

            const item = element.getRootElement();
            let serial, invalidElements;
            if (item) {
                serial = element.getSerial();
                invalidElements = item.data('invalid') || {};

                delete invalidElements[serial];
                item.data('invalid', invalidElements);
            }
        },
        valid(element, key) {

            const item = element.getRootElement();
            const serial = element.getSerial();
            let invalidElements;

            if (item) {
                invalidElements = item.data('invalid') || {};

                if (key) {

                    if (invalidElements[serial] && invalidElements[serial][key]) {
                        delete invalidElements[serial][key];
                        if (!_.size(invalidElements[serial])) {
                            delete invalidElements[serial];
                        }

                        item.data('invalid', invalidElements);
                    }

                } else {
                    throw new Error('missing required argument "key"');
                }
            }
        },
        invalid(element, key, message, stateName) {

            const item = element.getRootElement();
            const serial = element.getSerial();
            let invalidElements;

            if (item) {
                invalidElements = item.data('invalid') || {};

                if (key) {

                    if (!invalidElements[serial]) {
                        invalidElements[serial] = {};
                    }

                    invalidElements[serial][key] = {
                        message : message || '',
                        stateName : stateName || 'active'
                    };
                    item.data('invalid', invalidElements);

                } else {
                    throw new Error('missing required arguments "key"');
                }
            }
        },
        isValid(element) {

            const item = element.getRootElement();
            const serial = element.getSerial();
            let invalidElements;

            if (item) {
                invalidElements = item.data('invalid') || {};
                return !invalidElements[serial];
            }
            return true;
        }
    };

    return invalidator;
});


