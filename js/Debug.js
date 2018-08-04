let Debug = {

    uncircle: function (key, value) {
        if (typeof value === 'object' && value !== null) {
            try {
                // If this value does not reference a parent it can be deduped
                return JSON.parse(JSON.stringify(value));
            } catch (error) {
                // discard key if value cannot be deduped
                return;
            }
        }
        return value;
    }

};
