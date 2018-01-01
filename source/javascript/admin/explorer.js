﻿import xHttp from "../xHttp.js";
import File from "./File.js";
import openPopup from "./openPopup.js";
import {injectHtml, setCookie, getCookie, htmlEncode, removeTagById} from "./javascript.js";

var fileId = null;
var returnType = "";
var activeDir = getCookie("admin_dir");

var contextMenuFileTile;
var contextMenuImageTile;

function dirToId(dir) {
    return "dir_" + dir.replace(/\//g, ".");
}

function getContextMenuTarget(object, className) {
    while (object.className !== className) {
        object = object.parentNode;
    }

    return object;
}

function reattachContextMenus() {
    contextMenuFileTile.reattach();
    contextMenuImageTile.reattach();
}

function fileMoveDialog(id) {
    openPopup("/admin/explorer/move/" + id + "/", "fileMove", 322, 512);
}

function showFileName(id) {
    document.getElementById("navn" + id + "div").style.display = "none";
    document.getElementById("navn" + id + "form").style.display = "";
    document.getElementById("navn" + id + "form").firstChild.firstChild.select();
    document.getElementById("navn" + id + "form").firstChild.firstChild.focus();
}

function deleteFileCallback(data) {
    document.getElementById("loading").style.visibility = "hidden";
    if (data.error) {
        return;
    }

    removeTagById("tilebox" + data.id);
    delete window.files[data.id];
}

function deleteFile(id) {
    if (confirm("Vil du slette '" + window.files[id].name + "'?")) {
        document.getElementById("loading").style.visibility = "";
        xHttp.request("/admin/explorer/files/" + id + "/", deleteFileCallback, "DELETE");
    }
}

function openImageEditor(id) {
    openPopup("/admin/explorer/files/" + id + "/image/edit/", "imageEdit", 740, 600);
}

function editDescriptionCallback(data) {
    document.getElementById("loading").style.visibility = "hidden";
    if (data.error) {
        return;
    }

    window.files[data.id].description = data.description;
}

var editDescriptionRequest;
function editDescription(id) {
    var newalt = prompt("Billed beskrivelse", window.files[id].description);
    if (newalt === null || newalt === window.files[id].description) {
        return;
    }

    document.getElementById("loading").style.visibility = "";

    var data = {"description": newalt};
    xHttp.cancel(editDescriptionRequest);
    editDescriptionRequest =
        xHttp.request("/admin/explorer/files/" + id + "/description/", editDescriptionCallback, "PUT", data);
}

function openImageThumbnail(id) {
    openPopup("/admin/explorer/files/" + id + "/image/edit/?mode=thb", "imageThumbnail", 740, 600);
}

var fileTileContextMenu = [
    {
      "name": "Åbne",
      "className": "eye",
      callback(e) {
          var id = getContextMenuTarget(e.target, "filetile").id.match(/[0-9]+/g)[0];
          window.files[id].openfile();
      }
    },
    {
      "name": "Flyt",
      "className": "folder_go",
      callback(e) {
          var id = getContextMenuTarget(e.target, "filetile").id.match(/[0-9]+/g)[0];
          fileMoveDialog(id);
      }
    },
    {
      "name": "Omdøb",
      "className": "textfield_rename",
      callback(e) {
          var id = getContextMenuTarget(e.target, "filetile").id.match(/[0-9]+/g)[0];
          showFileName(id);
      }
    },
    {
      "name": "Slet",
      "className": "delete",
      callback(e) {
          var id = getContextMenuTarget(e.target, "filetile").id.match(/[0-9]+/g)[0];
          deleteFile(id);
      }
    }
];

var imageTileContextMenu = [{
    "name": "Åbne",
    "className": "picture",
    callback(e) {
        var id = getContextMenuTarget(e.target, "imagetile").id.match(/[0-9]+/g)[0];
        window.files[id].openfile();
    }
}];

if (window.location.href.match(/return=ckeditor/g)) {
    imageTileContextMenu.push({
        "name": "Indsæt link",
        "className": "link",
        callback(e) {
            var id = getContextMenuTarget(e.target, "imagetile").id.match(/[0-9]+/g)[0];
            window.files[id].addToEditor();
        }
    });
}
imageTileContextMenu = imageTileContextMenu.concat([
    {
      "name": "Rediger",
      "className": "picture_edit",
      callback(e) {
          var id = getContextMenuTarget(e.target, "imagetile").id.match(/[0-9]+/g)[0];
          openImageEditor(id);
      }
    },
    {
      "name": "Beskrivelse",
      "className": "textfield_rename",
      callback(e) {
          var id = getContextMenuTarget(e.target, "imagetile").id.match(/[0-9]+/g)[0];
          editDescription(id);
      }
    },
    {
      "name": "Generere ikon",
      "className": "pictures",
      callback(e) {
          var id = getContextMenuTarget(e.target, "imagetile").id.match(/[0-9]+/g)[0];
          openImageThumbnail(id);
      }
    },
    {
      "name": "Flyt",
      "className": "folder_go",
      callback(e) {
          var id = getContextMenuTarget(e.target, "imagetile").id.match(/[0-9]+/g)[0];
          fileMoveDialog(id);
      }
    },
    {
      "name": "Omdøb",
      "className": "textfield_rename",
      callback(e) {
          var id = getContextMenuTarget(e.target, "imagetile").id.match(/[0-9]+/g)[0];
          showFileName(id);
      }
    },
    {
      "name": "Slet",
      "className": "delete",
      callback(e) {
          var id = getContextMenuTarget(e.target, "imagetile").id.match(/[0-9]+/g)[0];
          deleteFile(id);
      }
    }
]);

function injectFileData(data) {
    injectHtml(data);
    files = {};
    data.files.forEach(function(fileData) {
        window.files[fileData.id] = new File(fileData);
    });
    console.log(files);
    reattachContextMenus();
}

var showFilesRequest = null;
function showfiles(dir) {
    // TODO, scroll to top.
    activeDir = dir;
    setCookie("admin_dir", activeDir, 360);

    document.getElementById("loading").style.visibility = "";
    var dirlist = document.getElementById("dir").getElementsByTagName("a");
    for (const element of dirlist) {
        element.className = "";
    }
    document.getElementById(dirToId(dir)).getElementsByTagName("a")[0].className = "active";

    xHttp.cancel(showFilesRequest);
    showFilesRequest = xHttp.request(
        "/admin/explorer/files/?path=" + encodeURIComponent(dir) + "&return=" + encodeURIComponent(returnType),
        injectFileData);
}

function getSelect(id) {
    var object = document.getElementById(id);
    return object[object.selectedIndex].value;
}

var searchfilesRequest = null;
function searchfiles() {
    document.getElementById("loading").style.visibility = "";
    var qpath = document.getElementById("searchpath").value;
    var qalt = document.getElementById("searchalt").value;
    var qtype = getSelect("searchtype");

    xHttp.cancel(searchfilesRequest);
    searchfilesRequest = xHttp.request("/admin/explorer/search/?qpath=" + encodeURIComponent(qpath) + "&qalt=" +
                                           encodeURIComponent(qalt) + "&qtype=" + encodeURIComponent(qtype) +
                                           "&return=" + encodeURIComponent(returnType),
                                       injectFileData);
}

function showdirname(nameObj) {
    nameObj.style.display = "none";
    nameObj.nextSibling.style.display = "inline";
    nameObj.nextSibling.firstChild.childNodes[1].select();
    nameObj.nextSibling.firstChild.childNodes[1].focus();
}

function idToDir(id) {
    return id.substr(4).replace(/[.]/g, "/");
}

function renameFolderCallback(data) {
    document.getElementById("loading").style.visibility = "hidden";
    var form = document.getElementById(dirToId(data.path)).getElementsByTagName("form")[0];
    if (data.error) {
        form.firstChild.childNodes[1].value = form.previousSibling.title;
        return;
    }

    if (data.yesno) {
        if (confirm(data.yesno)) {
            document.getElementById("loading").style.visibility = "";
            var payload = {
                "path": data.path,
                "name": form.firstChild.childNodes[1].value,
                "overwrite": true,
            };
            xHttp.request("/admin/explorer/folders/", renameFolderCallback, "PUT", payload);
            return;
        }
        form.firstChild.childNodes[1].value = form.previousSibling.title;
        return;
    }

    if (data.newPath !== data.path) {
        setCookie("admin_dir", data.newPath, 360);
        window.location.reload();
    }
}

function renamedir(newNameObj) {
    newNameObj.parentNode.parentNode.style.display = "none";
    newNameObj.parentNode.parentNode.previousSibling.style.display = "";
    document.getElementById("loading").style.visibility = "";
    var payload = {"path": idToDir(newNameObj.parentNode.parentNode.parentNode.id), "name": newNameObj.value};
    xHttp.request("/admin/explorer/folders/", renameFolderCallback, "PUT", payload);
}

function deleteFolderCallback(data) {
    window.location.reload();
}

function deleteFolder() {
    // TODO hvilket folder?
    if (confirm("Er du sikker på du vil slette denne mappe og dens indhold?")) {
        xHttp.request("/admin/explorer/folders/?path=" + encodeURIComponent(activeDir), deleteFolderCallback, "DELETE");
        setCookie("admin_dir", "", 360);
    }
}

function makedir_r(data) {
    document.getElementById("loading").style.visibility = "hidden";
    if (data.error) {
        return;
    }

    window.location.reload();
}

function makedir() {
    var name = prompt("Hvad skal mappen hede?", "Ny mappe");
    if (name) {
        document.getElementById("loading").style.visibility = "";
        xHttp.request(
            "/admin/explorer/folders/?path=" + encodeURIComponent(activeDir) + "&name=" + encodeURIComponent(name),
            makedir_r, "POST");
    }
}

function dir_expand_r(data) {
    document.getElementById("loading").style.visibility = "hidden";
    if (data.error) {
        return;
    }

    var dirdiv = document.getElementById(dirToId(data.id));
    dirdiv.firstChild.style.display = "none";
    dirdiv.childNodes[1].style.display = "";
    dirdiv.lastChild.innerHTML = data.html;
    dirdiv.lastChild.style.display = "";
}

function dir_expand(dirdiv, move) {
    dirdiv = dirdiv.parentNode;
    if (dirdiv.lastChild.firstChild === null) {
        document.getElementById("loading").style.visibility = "";
        xHttp.request(
            "/admin/explorer/folders/?path=" + encodeURIComponent(idToDir(dirdiv.id)) + "&move=" + (move ? 1 : 0),
            dir_expand_r);
        return;
    }

    dirdiv.lastChild.style.display = "";
    dirdiv.firstChild.style.display = "none";
    dirdiv.childNodes[1].style.display = "";
}

function dir_contract(obj) {
    obj = obj.parentNode;
    obj.lastChild.style.display = "none";
    obj.firstChild.style.display = "";
    obj.childNodes[1].style.display = "none";
}

function open_file_upload() {
    openPopup("/admin/explorer/upload/?path=" + encodeURIComponent(activeDir), "fileUpload", 640, 150);
}

function renamefile_r(data) {
    document.getElementById("loading").style.visibility = "hidden";
    if (data.error) {
        document.getElementById("navn" + data.id + "form").firstChild.firstChild.value = window.files[data.id].name;
        return;
    }

    if (data.yesno) {
        if (confirm(data.yesno)) {
            document.getElementById("loading").style.visibility = "";
            var payload = {
                "name": document.getElementById("navn" + data.id + "form").firstChild.firstChild.value,
                "overwrite": true
            };
            xHttp.request("/admin/explorer/files/" + data.id + "/", renamefile_r, "PUT", payload);
            return;
        }

        document.getElementById("navn" + data.id + "form").firstChild.firstChild.value = window.files[data.id].name;
        return;
    }

    document.getElementById("navn" + data.id + "div").innerText = data.filename;
    document.getElementById("navn" + data.id + "div").title = data.filename;
    document.getElementById("navn" + data.id + "form").firstChild.firstChild.value = data.filename;
    window.files[data.id].name = data.filename;
    window.files[data.id].path = data.path;
}

// TODO if force, refresh folder or we might have duplicates displaying in the folder.
function renamefile(id) {
    document.getElementById("navn" + id + "form").style.display = "none";
    document.getElementById("navn" + id + "div").style.display = "";
    var payload = {"name": document.getElementById("navn" + id + "form").firstChild.firstChild.value};
    document.getElementById("loading").style.visibility = "";
    xHttp.request("/admin/explorer/files/" + id + "/", renamefile_r, "PUT", payload);
}

var moveFileGlobal;
function movefile_r(data) {
    window.opener.document.getElementById("loading").style.display = "none";

    if (data.newPath === data.path) {
        return;
    }

    if (data.error) {
        return;
    }
    if (data.yesno) {
        if (confirm(data.yesno)) {
            document.getElementById("loading").style.display = "";
            var requestData = {"dir": moveFileGlobal, "overwrite": true};
            xHttp.request("/admin/explorer/files/" + fileId + "/", movefile_r, "PUT", requestData);
        }
        return;
    }

    window.opener.document.getElementById("files").removeChild(
        window.opener.document.getElementById("tilebox" + data.id));
    window.close();
}

function movefile(dir) {
    moveFileGlobal = dir;
    window.opener.document.getElementById("loading").style.display = "";
    var data = {"dir": dir};
    xHttp.request("/admin/explorer/files/" + fileId + "/", movefile_r, "PUT", data);
}

function swap_pannel(navn) {
    // Save what mode we are in and what was searched for
    if (navn === "search") {
        document.getElementById("files").innerText = "";
        document.getElementById("dir_bn").className = "";
        document.getElementById("dir").style.display = "none";
        document.getElementById("search_bn").className = "down";
        document.getElementById("search").style.display = "";
        if (document.getElementById("searchpath").value || document.getElementById("searchalt").value ||
            getSelect("searchtype")) {
            searchfiles();
        }
    } else if (navn === "dir") {
        document.getElementById("files").innerText = "";
        document.getElementById("search_bn").className = "";
        document.getElementById("search").style.display = "none";
        document.getElementById("dir_bn").className = "down";
        document.getElementById("dir").style.display = "";
        showfiles(activeDir);
    }
    return false;
}

window.addEventListener("DOMContentLoaded", function(event) {
    window.files = {};
    window.showfiles = showfiles;
    window.searchfiles = searchfiles;
    window.showdirname = showdirname;
    window.renamedir = renamedir;
    window.deleteFolder = deleteFolder;
    window.makedir = makedir;
    window.dir_expand = dir_expand;
    window.dir_contract = dir_contract;
    window.open_file_upload = open_file_upload;
    window.renamefile = renamefile;
    window.movefile = movefile;
    window.swap_pannel = swap_pannel;

    returnType = window.returnType || "";
    fileId = window.fileId || null;

    // attach context menus
    contextMenuFileTile =
        new Proto.Menu({"selector": ".filetile", "className": "menu desktop", "menuItems": fileTileContextMenu});
    contextMenuImageTile =
        new Proto.Menu({"selector": ".imagetile", "className": "menu desktop", "menuItems": imageTileContextMenu});

    if (!activeDir || !document.getElementById(dirToId(activeDir))) {
        activeDir = "/images";
    }

    showfiles(activeDir);
});