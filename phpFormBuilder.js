document.addEventListener("DOMContentLoaded", function() {

    var varFlagObjects = ${objFlags};
    var ${objectname} = {
        getForm: function() {
            var e = document.getElementById("${f}");
            return void 0 !== e && null != e || (e = document.getElementsByTagName("form")[0]), void 0 !== e && null != e && e
        },
        getGroups: function() {
            var e = document.querySelectorAll("[group]"),
                t = "",
                r = {};
            return e.forEach(e => {
                void 0 !== (t = e.getAttribute("group")) && (r[t] = "")
            }), 0 != r.length && Object.keys(r).reverse()
        },
        buildGroupFieldsets: function() {
            var e = ${objectname}.getForm();
            e && ${objectname}.getGroups().forEach(t => {
                let r = document.createElement("fieldset"),
                    o = document.createElement("legend");
                o.innerHTML = t, r.appendChild(o);
                let n = '.form_field_wrap[group="' + t + '"]';
                document.querySelectorAll(n).forEach(e => {
                    r.append(e)
                }), e.insertBefore(r, e.firstChild)
            })
        },
        buildFlags: function(){
            /* 
            - Creates the flag checkboxes 
                plus a hidden control to receive the values.
            - Creates the click events
            */
            varFlagObjects.forEach(t => {
                // there's a div up there with the id t.id
                // the text inside it is the current record's flag value
                let C = document.getElementById(t.id);
                let curFlag = parseInt(C.innerText);
                let e = C.parentNode;
                C.remove();
                let D = JSON.parse(t.values);
                let f = document.createElement("input");
                f.type = 'hidden'; // make this 'text' if you want to see the thing in action
                f.value = curFlag;
                f.id = t.id;
                f.name = t.id;
                e.append(f);
                D.forEach(a => {
                    let b = document.createElement("label");
                    b.innerHTML = a.caption;
                    let c = document.createElement("input");
                    c.type = 'checkbox';
                    c.value = a.value;
                    c.setAttribute('feeds', t.id);
                    c.checked = ( (a.value & curFlag) != 0 );
                    c.onclick = function(){
                        let newflag = 0;
                        let g = document.querySelectorAll('[feeds="' + t.id + '"]');
                        g.forEach(h => {
                            if(h.checked) {
                                newflag += (h.value * 1);
                            }
                        });
                        f.value = newflag;
                    };
                    e.append(b);
                    e.append(c);
                });
                
            });
        }
    };
    ${objectname}.buildGroupFieldsets();
    ${objectname}.buildFlags();


});