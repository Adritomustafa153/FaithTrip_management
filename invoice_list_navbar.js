import "mdb-ui-kit/css/mdb.min.css";
import "mdb-ui-kit";

document.addEventListener("DOMContentLoaded", function () {
    const myModal = new mdb.Modal(document.getElementById("exampleModal"));
  });
// Initialization for ES Users
import { Dropdown, Collapse, initMDB } from "mdb-ui-kit";

initMDB({ Dropdown, Collapse });  