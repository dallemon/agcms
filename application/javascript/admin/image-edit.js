// TODO avoide overscaling when triming has been in affect
var imageSaveRequest;
function saveImage(overwrite = false) {
    $("save").style.display = "none";
    $("loading").style.visibility = "";

    var dimention = calcImageDimension();

    var data = {
        "cropX": cropX,
        "cropY": cropY,
        "cropW": maxW,
        "cropH": maxH,
        "maxW": dimention.width,
        "maxH": dimention.height,
        "flip": flip,
        "rotate": rotate
    };
    xHttp.cancel(imageSaveRequest);
    var methode = "PUT";
    if (mode === "thb") {
        methode = "POST";
    }
    imageSaveRequest = xHttp.request("/admin/explorer/files/" + id + "/image/", saveImage_r, methode, data);
}

function calcImageDimension() {
    var dimention = {"width": Math.round(maxW * scale), "height": Math.round(maxH * scale)};

    if (mode === "thb") {
        var width = thumb_width;
        var height = thumb_height;
        if (rotate % 180) {
            width = thumb_height;
            height = thumb_width;
        }
        dimention.width = Math.min(width, dimention.width);
        dimention.height = Math.min(height, dimention.height);
    }

    return dimention;
}

function saveImage_r(data) {
    $("loading").style.visibility = "hidden";
    $("save").style.display = "";
    if (data.error) {
        return;
    }
    if (window.opener.returnid) {
        window.opener.opener.document.getElementById(window.opener.returnid).value = data.id;
        window.opener.opener.document.getElementById(window.opener.returnid + "thb").src = data.path;
        // TODO make shure theas closes
        window.opener.close();
    } else if (window.opener.files[data.id]) {
        window.opener.files[data.id].width = data.width;
        window.opener.files[data.id].height = data.height;
        window.opener.files[data.id].refreshThumb();
    } else {
        window.opener.location.reload(true);
    }

    window.close();
}

var CropImageManager = {
    // Holds the current Cropper.Img object
    curCrop: null,

    // Initialises the cropImageManager
    init: function() {},

    // Attaches/resets the image cropper
    attachCropper: function() {
        if (resizeHandle != null) {
            resizeEnd();
        }
        $("preview").style.display = "none";
        $("original").style.display = "";
        $("resetCropper").style.display = "none";
        $("removeCropper").style.display = "";
        $("preview").style.width = "";
        if (this.curCrop != null) {
            this.curCrop.remove();
        }
        this.curCrop = new Cropper.Img("original", {
            "onEndCrop": onEndCrop,
            "displayOnInit": true,
            "onloadCoords": {x1: cropX, y1: cropY, x2: maxW + cropX, y2: maxH + cropY}
        });
    },
    // Removes the cropper
    removeCropper: function() {
        $("original").style.display = "none";
        $("preview").style.display = "";
        $("removeCropper").style.display = "none";
        $("resetCropper").style.display = "";
        preview();
        if (this.curCrop != null) {
            this.curCrop.remove();
        }
    },
    // Resets the cropper, either re-setting or re-applying
    resetCropper: function() {
        this.attachCropper();
    }
};

// setup the callback function
function onEndCrop(coords, dimensions) {
    cropX = coords.x1;
    cropY = coords.y1;
    maxW = dimensions.width;
    maxH = dimensions.height;

    if (mode === "thb") {
        if (rotate) {
            scale = Math.min(1, Math.max(thumb_height / maxW, thumb_width / maxH));
            return;
        }
        scale = Math.min(1, Math.max(thumb_width / maxW, thumb_height / maxH));
    }
}

// basic example
Event.observe(window, "load", function test() {
    CropImageManager.init();
    Event.observe($("save"), "click", CropImageManager.removeCropper.bindAsEventListener(CropImageManager), false);
    Event.observe($("flipV"), "click", CropImageManager.removeCropper.bindAsEventListener(CropImageManager), false);
    Event.observe($("flipH"), "click", CropImageManager.removeCropper.bindAsEventListener(CropImageManager), false);
    Event.observe($("cw"), "click", CropImageManager.removeCropper.bindAsEventListener(CropImageManager), false);
    Event.observe($("ccw"), "click", CropImageManager.removeCropper.bindAsEventListener(CropImageManager), false);
    Event.observe($("removeCropper"), "click", CropImageManager.removeCropper.bindAsEventListener(CropImageManager),
                  false);
    Event.observe($("resetCropper"), "click", CropImageManager.resetCropper.bindAsEventListener(CropImageManager),
                  false);
});

var resizeHandle = null;
function resize() {
    $("save").style.display = "";
    $("loading").style.visibility = "hidden";
    if (resizeHandle != null) {
        resizeHandle.destroy();
    }

    $("resizeHandle").style.left = $("preview").width + "px";
    $("resizeHandle").style.top = $("preview").height + "px";

    var maxWH = (rotate === 90 || rotate === 270) ? maxH : maxW;
    if (mode === "thb") {
        maxWH = Math.round(maxWH * Math.min(1, Math.min(thumb_width / maxW, thumb_height / maxH)));
    }

    resizeHandle = new Draggable("resizeHandle", {
        constraint: "horizontal",
        onDrag: function(obj, e) {
            var width = parseInt(obj.element.style.left);
            if (width < 16) {
                width = 16;
            }
            if (width > maxWH) {
                width = maxWH;
            }

            $("preview").style.width = width + "px";
            $("resizeHandle").style.top = $("preview").height + "px";
            scale = width / (rotate ? maxH : maxW);
        },
        onEnd: function(e) {
            preview();
        }
    });
    $("resizeHandle").style.display = "";
}

function resizeEnd() {
    resizeHandle.destroy();
    $("resizeHandle").style.display = "none";
}

var cropX = 0;
var cropY = 0;
var orientation = 1; // 1-4,11-14
var rotate = 0;      // 90,180,270
var flip = 0;        // 1,2

function rotateCCW() {
    var move = 1;
    if (orientation === 4 || orientation === 11) {
        move = -3;
    }
    updateOrientation(move);
}

function rotateCW() {
    var move = -1;
    if (orientation === 1 || orientation === 14) {
        move = 3;
    }
    updateOrientation(move);
}

function flipHorizontal() {
    updateOrientation(10);
}

function flipVertical() {
    var move = 12;
    if (orientation === 3 || orientation === 11 || orientation === 4 || orientation === 12) {
        move = 8;
    }
    updateOrientation(move);
}

function updateOrientation(move) {
    orientation += orientation < 10 ? move : -move;
    switch (orientation) {
        case 1:
            rotate = 0;
            flip = 0;
            break;
        case 2:
            rotate = 90;
            flip = 0;
            break;
        case 3:
            rotate = 180;
            flip = 0;
            break;
        case 4:
            rotate = 270;
            flip = 0;
            break;
        // fliped
        case 11:
            rotate = 0;
            flip = 1;
            break;
        case 12:
            rotate = 270;
            flip = 1;
            break;
        case 13:
            rotate = 0;
            flip = 2; // faster then rotates
            break;
        case 14:
            rotate = 90;
            flip = 1;
            break;
    }
    preview();
    $("preview").style.width = "";
}

function preview() {
    $("save").style.display = "none";
    $("loading").style.visibility = "";

    var dimention = calcImageDimension();

    $("preview").src = "/admin/explorer/files/" + id + "/image/?cropX=" + cropX + "&cropY=" + cropY + "&cropW=" + maxW +
                       "&cropH=" + maxH + "&maxW=" + dimention.width + "&maxH=" + dimention.height + "&rotate=" +
                       rotate + "&flip=" + flip;
}
