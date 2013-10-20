var components = {
    "packages": [
        {
            "name": "jquery",
            "main": "jquery-built.js"
        },
        {
            "name": "jquery-ui",
            "main": "jquery-ui-built.js"
        },
        {
            "name": "underscore",
            "main": "underscore-built.js"
        }
    ],
    "shim": {
        "jquery-ui": {
            "deps": [
                "jquery"
            ],
            "exports": "jQuery"
        },
        "underscore": {
            "exports": "_"
        }
    },
    "baseUrl": "components"
};
if (typeof require !== "undefined" && require.config) {
    require.config(components);
} else {
    var require = components;
}
if (typeof exports !== "undefined" && typeof module !== "undefined") {
    module.exports = components;
}