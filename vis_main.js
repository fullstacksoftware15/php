/**
 * vis_main.js
 * For displaying Reddit Robin tier data as a visual family tree
 * By /u/kwwxis
 */

var vis_global = {
    base_style:' \
        .link { \
            fill: none; \
            stroke: #ccc; \
            stroke-width: 2px; \
        } \
        .node circle { \
            cursor: pointer; \
            fill: #fff; \
            stroke: steelblue; \
            stroke-width: 1.5px; \
        } \
        .node text { \
            font-size: 11px; \
        } \
        path.link { \
            fill: none; \
            stroke: #ccc; \
            stroke-width: 1.5px; \
        } \
        .vis-tooltip { \
            position: absolute; \
            text-align: center; \
            padding: 4px 10px; \
            font-size: 12px; \
            color: white; \
            background: rgba(0,0,0,0.8); \
            border: 0px; \
            border-radius: 3px; \
            pointer-events: none; \
        } \
        ',
    isFirstCall: true,
    firstCall: function() {
        if (!this.isFirstCall)
            return;
        this.isFirstCall = false;
        
        var head = document.head || document.getElementsByTagName('head')[0],
            style = document.createElement('style');

        style.type = 'text/css';
        if (style.styleSheet){
            style.styleSheet.cssText = this.base_style;
        } else {
            style.appendChild(document.createTextNode(this.base_style));
        }

        head.appendChild(style);
        
        this.tooltip = d3.select("body").append("div")	
                .attr("class", "vis-tooltip")				
                .style("opacity", 0)
    },
    list: [],
    tooltip: null,
};

function vis_main(vis_sel) {
    var instance = this,
        isStarted = false,
        selector = vis_sel,
        root,
        tree,
        svg_el,
        svg_g,
        diagonal,
        zm,
        simple_data = [],
        data = [],
        radial_mode = false,
    
        // viewport dimensions
        vw = Math.max(document.documentElement.clientWidth, window.innerWidth || 0),
        vh = Math.max(document.documentElement.clientHeight, window.innerHeight || 0),
        // svg dimensions
        w = vw < 800 ? vw : vw - 120,
        h = vh - 40,
        // diameter (radial tree)
        di = 960,
        // node next id
        i = 0,
        // zoom min, max, initial
        zm_min = 0.1,
        zm_max = 10,
        zm_init = 1,
        zm_curr = zm_init,
        // pan initial
        px_init = 120,
        py_init = w / 3,
        pn_init_set = false,
        pn_crr = [px_init, py_init];
        
    vis_global.list.push(this);
    
    // *************** Mode functions ***************
    // Enable/disable radial mode. No parameter = enable
    
    this.radial = function(set) {
        if (arguments.length == 1) {
            radial_mode = set;
        } else {
            radial_mode = true;
        }
        return this;
    }
    
    this.isNormal = function() {
        return !radial_mode;
    }
    
    this.isRadial = function() {
        return radial_mode;
    }
    
    // *************** Main functions ***************
    
    // Updates root based on what's in simple_data and/or data
    this.pack = function(level_n) {
        var treeData = [];
        
        if (simple_data.length != 0) {
            for (var i = 0; i < simple_data.length; i++) {
                var item_data0 = simple_data[i].split("+");
                var item_data1 = item_data0[1].split("=");
                var child0 = item_data0[0].split("?");
                var child1 = item_data1[0].split("?");
                var parent = item_data1[1].trim();
                
                var append0 = {
                    "name": child0[0].trim(),
                    "parent": parent
                };
                if (child0.length == 2) {
                    append0.tooltip = child0[1].trim();
                }
                
                var append1 = {
                    "name": child1[0].trim(),
                    "parent": parent
                };
                if (child1.length == 2) {
                    append1.tooltip = child1[1].trim();
                }
                
                
                if (child0[0].trim() != "null") {
                    console.log(append0);
                    data.push(append0);
                }
                if (child1[0].trim() != "null") {
                    console.log(append1);
                    data.push(append1);
                }
            }
        }
        
        if (data.length == 0) {
            return;
        }
        
        // Convert flat data to tree
        
        var dataMap = data.reduce(function(map, node) {
            map[node.name] = node;
            return map;
        }, {});
        
        // create the tree array
        data.forEach(function(node) {
            var parent = dataMap[node.parent];
            if (parent) {
                (parent.children || (parent.children = [])).push(node);
            } else {
                treeData.push(node);
            }
        });
        
        root = treeData[0];
        if (Number(level_n) === level_n && level_n % 1 == 0 && level_n >= 0) {
            this.collapseAfter(root, level_n);
        }
        this._evaluate();
    };
    
    this._evaluate = function() {
        root.x0 = h / 2;
        root.y0 = 0;
        
        if (tree && svg_el) {
            tree.size([h, w]);
            svg_el.attr('width',  w)
               .attr('height', h);
        }
    }
    
    // @param level_n - how many levels deep the tree should be until auto collapse
    this.start = function(level_n) {
        vis_global.firstCall();
        
        if (isStarted) {
            return false;
        }
        isStarted = true;
        
        this.pack(level_n);
        
        tree = d3.layout.tree()
            .size([h, w])
            .nodeSize([30, 150]);
        
        if (this.isNormal()) {
            tree.separation(function(a, b) {
                return (a.parent == b.parent ? 1 : 1.5);
            });
        } else if (this.isRadial()) {
            tree.separation(function(a, b) {
                return (a.parent == b.parent ? 1 : 2) / a.depth;
            });
        }
        
        if (this.isNormal() || this.isRadial()) {
            tree.size = function(x) {
                if (!arguments.length)
                    return nodeSize ? null : size;
                nodeSize = (size = x) == null;
                return tree;
            };

            tree.nodeSize = function(x) {
                if (!arguments.length) return nodeSize ? size : null;
                nodeSize = (size = x) != null;
                return tree;
            };
        }
        
        if (this.isNormal()) {
            diagonal = d3.svg.diagonal()
                .projection(function(d) { return [d.y, d.x]; });
        } else if (this.isRadial()) {
            diagonal = d3.svg.diagonal.radial()
                .projection(function(d) { return [d.y, d.x / 180 * Math.PI]; });
        }
        
        if (this.isNormal() || this.isRadial()) {
            var tmp_w = w,
                tmp_h = h;
            
            if (this.isRadial()) {
                tmp_w = tmp_h = di;
            }
            
            svg = d3.select(selector);
            svg_g = d3.select(selector)
                .attr('width',  tmp_w)
                .attr('height', tmp_h)
                .call(zm = d3.behavior.zoom().scaleExtent([zm_min,zm_max]).on("zoom", this._redraw))
                .append("svg:g")
                    .attr("transform", "translate(" + px_init + "," + py_init + ")scale("+zm_init+")");  
            
            zm.translate([px_init, py_init]).scale(zm_init);
        }
        
        if (root == null) {
            // If root is null, no data was passed in
            // which is fine (can be passed in later)
            return this;
        } else {
            this.update(root);
            return this;
        }
    };
    
    this.update = function(source) {
        if (this.isRadial()) {
            // compute the new height
            var levelWidth = [1];
            var childCount = function(level, n) {
                if(n.children && n.children.length > 0) {
                    if(levelWidth.length <= level + 1)
                        levelWidth.push(0);
                    
                    levelWidth[level+1] += n.children.length;
                    n.children.forEach(function(d) {
                        childCount(level + 1, d);
                    });
                }
            };
            childCount(0, root);  
            var newHeight = d3.max(levelWidth) * 20; // 20 pixels per line  
            tree = tree.size([newHeight, w]);
            
            var nodes = tree.nodes(root),
                links = tree.links(nodes);

            var link = svg_g.selectAll(".link")
                    .data(links)
                .enter().append("path")
                    .attr("class", "link")
                    .attr("d", diagonal);
            
            var node = svg_g.selectAll(".node").data(nodes);
            var nodeEnter = node.enter()
                .append("g")
                    .attr("class", "node")
                    .attr("transform", function(d) { return "rotate(" + (d.x - 90) + ")translate(" + d.y + ")"; })

            nodeEnter.append("circle")
                .attr("r", 4.5);

            nodeEnter.append("text")
                .attr("dy", ".31em")
                .attr("data-dx", function(d) {return d.x;})
                .attr("text-anchor", function(d) { return d.x < 180 ? "start" : "end"; })
                .attr("transform", function(d) { return d.x > 0 ? "translate(8)" : "rotate(180)translate(-100)"; })
                .text(function(d) { return d.name; });
        }
        
        if (this.isNormal()) {
            var duration = d3.event && d3.event.altKey ? 5000 : 500;
            
            // compute the new height
            var levelWidth = [1];
            var childCount = function(level, n) {
                if(n.children && n.children.length > 0) {
                    if(levelWidth.length <= level + 1)
                        levelWidth.push(0);
                    
                    levelWidth[level+1] += n.children.length;
                    n.children.forEach(function(d) {
                        childCount(level + 1, d);
                    });
                }
            };
            childCount(0, root);  
            var newHeight = d3.max(levelWidth) * 20; // 20 pixels per line  
            tree = tree.size([newHeight, w]);
        
            // Compute the new tree layout
            var nodes = tree.nodes(root).reverse();

            // Normalize for fixed-depth
            nodes.forEach(function(d) { d.y = d.depth * 180; });

            // Update the nodes
            var node = svg_g.selectAll("g.node")
                .data(nodes, function(d) { return d.id || (d.id = ++i); });
            
            // Drag behavior not wanted
            var drag = d3.behavior.drag()
                .on('dragstart', function () {
                    d3.event.sourceEvent.stopPropagation();
                    d3.event.sourceEvent.preventDefault();
                });
            
            // Enter any new nodes at the parent's previous position
            var nodeEnter = node.enter().append("svg:g")
                .attr("class", "node")
                .attr("transform", function(d) { return "translate(" + source.y0 + "," + source.x0 + ")"; })
                .on("click", function(d) { instance.toggle(d); instance.update(d); })
                .on('contextmenu', function(d) {
                    d3.event.preventDefault();
                    instance.toggleAll(d);
                    instance.update(d);
                })
                .call(drag);

            nodeEnter.append("svg:circle")
                .attr("r", 1e-6)
                .style("fill", function(d) { return d._children ? "lightsteelblue" : "#fff"; })
                .on("mouseover", function(d) {
                    if (d.tooltip) {
                        vis_global.tooltip.transition()
                            .duration(200)
                            .style("opacity", 0.9);
                            
                        var ttw     = vis_global.tooltip.node().getBoundingClientRect().width,
                            tth     = vis_global.tooltip.node().getBoundingClientRect().height,
                            hmin    = 28;
                            ttm     = 20; // margin between circle and tooltip
                            
                        vis_global.tooltip.html(d.tooltip)
                            .style("left", (d3.event.pageX - ttw/2) + "px")
                            .style("top",  (d3.event.pageY - Math.max(tth, hmin) - ttm) + "px");
                    }
                })
                .on("mouseout", function(d) {
                    if (d.tooltip) {
                        vis_global.tooltip.transition()
                            .duration(300)
                            .style("opacity", 0);
                    }
                });

            nodeEnter.append("svg:text")
                .attr("x", function(d) { return d.children || d._children ? -10 : 10; })
                .attr("dy", ".35em")
                .attr("text-anchor", function(d) { return d.children || d._children ? "end" : "start"; })
                .text(function(d) { return d.name; })
                .style("fill-opacity", 1e-6);

            // Translate nodes to their new position
            var nodeUpdate = node.transition()
                .duration(duration)
                .attr("transform", function(d) { return "translate(" + d.y + "," + d.x + ")"; });

            nodeUpdate.select("circle")
                .attr("r", 4.5)
                .style("fill", function(d) { return d._children ? "lightsteelblue" : "#fff"; });

            nodeUpdate.select("text")
                .style("fill-opacity", 1);

            // Translate exiting nodes to parent's new position
            var nodeExit = node.exit().transition()
                .duration(duration)
                .attr("transform", function(d) { return "translate(" + source.y + "," + source.x + ")"; })
                .remove();

            nodeExit.select("circle")
                .attr("r", 1e-6);

            nodeExit.select("text")
                .style("fill-opacity", 1e-6);

            // Update the links
            var link = svg_g.selectAll("path.link")
                .data(tree.links(nodes), function(d) { return d.target.id; });

            // Enter any new links at the parent's previous position
            link.enter().insert("svg:path", "g")
                .attr("class", "link")
                .attr("d", function(d) {
                    var o = {x: source.x0, y: source.y0};
                    return diagonal({source: o, target: o});
                })
                .transition()
                .duration(duration)
                    .attr("d", diagonal);

            // Transition links to their new position
            link.transition()
                .duration(duration)
                .attr("d", diagonal);

            // Transition exiting nodes to the parent's new position
            link.exit().transition()
                .duration(duration)
                .attr("d", function(d) {
                    var o = {x: source.x, y: source.y};
                    return diagonal({source: o, target: o});
                })
                .remove();

            // Stash the old positions for transition.
            nodes.forEach(function(d) {
                d.x0 = d.x;
                d.y0 = d.y;
            });
        }
        
        return this;
    };
    
    // For any changes to the data or dimensions after start(), this should be called
    this.reset = function() {
        this.pack();
        this.update(root);
    };
    
    this.getSelector = function() {
        return selector;
    };
    
    // *************** Main-Helper functions ***************
    
    // toggle a node d, calling update() is necessary to see changes
    this.toggle = function(d) {
        if (d.children) {
            d._children = d.children;
            d.children = null;
        } else {
            d.children = d._children;
            d._children = null;
        }
    };
    
    // either expands or collapses all nodes under `d`
    this.toggleAll = function(d) {
        if (d._children) {
            function expand(d) {
                if (d._children) {
                    d.children = d._children;
                    d.children.forEach(expand);
                    d._children = null;
                }
            }
            expand(d);
        } else if (d.children) {
            function collapse(d) {
                if (d.children) {
                    d._children = d.children;
                    d._children.forEach(collapse);
                    d.children = null;
                }
            };
            collapse(d);
        }
    }
    
    // Can only be used when called by a d3 event, do not call directly
    this._redraw = function() {
        zm_curr = d3.event.translate;
        pn_crr = d3.event.scale;
        svg_g.attr("transform",
            "translate(" + d3.event.translate + ")"
             + " scale(" + d3.event.scale + ")");
    };
    
    // *************** Collapse nodes ***************
    // collapse until the specified level deep
    // @param d      the node
    // @param level  specified level deep from the given node
    
    this.collapseAfter = function(d, level) {
        d = d || root;
        
        function collapse(d) {
            if (d.children) {
                d._children = d.children;
                d._children.forEach(collapse);
                d.children = null;
            }
        };
        
        if (level == 0) {
            collapse(d);
            return;
        }
        
        function _collapseAfter(d, level, i) {
            if (d) {
                if (i < level) {
                    if (d.children) {
                        _collapseAfter(d.children[0], level, i+1);
                        _collapseAfter(d.children[1], level, i+1);
                    }
                } else if (i == level) {
                    collapse(d);
                }
            }
        };
        
        
        _collapseAfter(d, level, 0);
        
        return this;
    };
    
    // *************** Get/Set data functions ***************
    // Any changes to the data after start() must be made active
    // by calling reset()
    
    this.getSimpleData = function() { return simple_data };
    this.setSimpleData = function(new_simple_data) {
        simple_data = new_simple_data;
        return this;
    };
    this.getData = function() { return data; };
    this.setData = function(new_data) {
        data = new_data;
        return this;
    };
    
    // *************** Dimension functions ***************
    // After start(), reset() must be called for any dimensions
    // changes to be active
    
    this.getViewportDimensions = function() {
        return [vw, vh];
    };
    
    this.getDimensions = function() {
        return [w, h];
    };
    this.setDimensions = function(dim) {
        w = dim[0];
        h = dim[1];
        return this;
    };
    
    this.getWidth = function() {
        return w;
    };
    this.setWidth = function(new_w) {
        w = new_w;
        return this;
    };
    
    this.getHeight = function() {
        return h;
    };
    this.setHeight = function(new_h) {
        h = new_h;
        return this;
    };
    
    this.getDiameter = function() {
        return di;
    }
    
    this.setDiameter = function(new_di) {
        di = new_di;
        if (!pn_init_set) {
            px_init = di / 2;
            py_init = di / 2;
            pn_crr = [px_init, py_init];
        }
        return this;
    }
    
    // *************** Zoom/Pan functions ***************
    // The set functions here only work before start()
    // reset() is not necessary to use pan,translate,zoom,scale,panzoom
    
    this.setZoomScale = function(min, max) {
        zm_min = min;
        zm_max = max;
        return this;
    };
    this.setZoomInit = function(zoom_init) {
        zm_init = zoom_init;
        if (!isStarted)
            zm_curr = zm_init;
        return this;
    };
    this.setPanInit = function(pan_init) {
        pn_init_set = true;
        px_init = pan_init[0],
        py_init = pan_init[1];
        if (!isStarted)
            pn_crr = [px_init, py_init];
        return this;
    };
    
    this.pan = function(new_pan) {
        this.panzoom(zm_curr, new_pan);
    };
    this.translate = function(new_pan) {
        this.pan(new_pan);
    };
    
    this.zoom = function(new_zoom) {
        this.panzoom(new_zoom, pn_crr);
    };
    this.scale = function(new_zoom) {
        this.zoom(new_zoom);
    };
    
    this.panzoom = function(new_zoom, new_pan) {
        zm.translate(new_pan).scale(new_zoom);
        svg_g.attr("transform",
            "translate(" + new_pan  + ")"
             + " scale(" + new_zoom + ")");
    };
    
    // *************** Get internal data functions ***************
    // these functions return data structures used internally, be
    // careful when using them. When changing data, it is recommended
    // you use setSimpleData or setData instead of directly modifying
    // the root
    
    this.getSVG  = function() { return svg_el; };
    this.getG    = function() { return svg_g; };
    this.getTree = function() { return tree; };
    this.getRoot = function() { return root; };
    this.getZoom = function() { return zm; };
    
    return this;
};