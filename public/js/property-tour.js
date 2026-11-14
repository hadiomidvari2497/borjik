(function () {
  'use strict';

  var dialog = document.querySelector('[data-property-tour]');
  if (!dialog) return;

  var canvas = dialog.querySelector('canvas');
  var stage = dialog.querySelector('.property-tour-stage');
  var fallback = dialog.querySelector('[data-tour-fallback]');
  var title = dialog.querySelector('[data-tour-title]');
  var roomsNav = dialog.querySelector('[data-tour-rooms]');
  var closeButton = dialog.querySelector('[data-tour-close]');
  var gl = canvas && (canvas.getContext('webgl', { alpha: false, antialias: true }) || canvas.getContext('experimental-webgl'));
  var program, texture, image, yaw = 0, pitch = 0, fov = 1.15;
  var dragging = false, lastX = 0, lastY = 0, roomIndex = 0, rooms = [], raf = 0;
  var projection = 'equirectangular', fitScale = 1, zoom = 1, offsetX = 0, offsetY = 0;
  var pointers = {}, pinchStart = 0, pinchZoom = 1;

  function shader(type, source) {
    var item = gl.createShader(type);
    gl.shaderSource(item, source);
    gl.compileShader(item);
    if (!gl.getShaderParameter(item, gl.COMPILE_STATUS)) throw new Error(gl.getShaderInfoLog(item));
    return item;
  }

  function setup() {
    var vertex = 'attribute vec2 p; varying vec2 uv; void main(){ uv=p*0.5+0.5; gl_Position=vec4(p,0.0,1.0); }';
    var fragment = 'precision mediump float; varying vec2 uv; uniform sampler2D pano; uniform float yaw; uniform float pitch; uniform float fov; uniform float aspect; const float PI=3.141592653589793; void main(){ float sx=(uv.x-0.5)*2.0*aspect*fov; float sy=(0.5-uv.y)*2.0*fov; vec3 ray=normalize(vec3(sin(yaw)+sx*cos(yaw), sy+sin(pitch), cos(yaw)-sx*sin(yaw))); float lon=atan(ray.x,ray.z); float lat=asin(clamp(ray.y,-1.0,1.0)); vec2 st=vec2(fract(lon/(2.0*PI)+0.5),0.5-lat/PI); gl_FragColor=texture2D(pano,st); }';
    program = gl.createProgram();
    gl.attachShader(program, shader(gl.VERTEX_SHADER, vertex));
    gl.attachShader(program, shader(gl.FRAGMENT_SHADER, fragment));
    gl.linkProgram(program);
    if (!gl.getProgramParameter(program, gl.LINK_STATUS)) throw new Error(gl.getProgramInfoLog(program));
    gl.useProgram(program);
    var buffer = gl.createBuffer();
    gl.bindBuffer(gl.ARRAY_BUFFER, buffer);
    gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1,-1,1,-1,-1,1,1,1]), gl.STATIC_DRAW);
    var loc = gl.getAttribLocation(program, 'p');
    gl.enableVertexAttribArray(loc);
    gl.vertexAttribPointer(loc, 2, gl.FLOAT, false, 0, 0);
    texture = gl.createTexture();
    gl.activeTexture(gl.TEXTURE0);
    gl.bindTexture(gl.TEXTURE_2D, texture);
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_S, gl.REPEAT);
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_T, gl.CLAMP_TO_EDGE);
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MIN_FILTER, gl.LINEAR);
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MAG_FILTER, gl.LINEAR);
    gl.uniform1i(gl.getUniformLocation(program, 'pano'), 0);
  }

  function resize() {
    if (!gl || projection !== 'equirectangular') return;
    var ratio = Math.min(window.devicePixelRatio || 1, 2);
    var w = Math.max(1, Math.floor(canvas.clientWidth * ratio));
    var h = Math.max(1, Math.floor(canvas.clientHeight * ratio));
    if (canvas.width !== w || canvas.height !== h) { canvas.width = w; canvas.height = h; }
    gl.viewport(0, 0, w, h);
  }

  function draw() {
    if (!gl || !image || projection !== 'equirectangular') return;
    resize();
    gl.useProgram(program);
    gl.uniform1f(gl.getUniformLocation(program, 'yaw'), yaw);
    gl.uniform1f(gl.getUniformLocation(program, 'pitch'), pitch);
    gl.uniform1f(gl.getUniformLocation(program, 'fov'), fov);
    gl.uniform1f(gl.getUniformLocation(program, 'aspect'), canvas.width / canvas.height);
    gl.drawArrays(gl.TRIANGLE_STRIP, 0, 4);
    raf = window.requestAnimationFrame(draw);
  }

  function clampFlatPan() {
    if (!image || !stage) return;
    var width = image.naturalWidth * fitScale * zoom;
    var height = image.naturalHeight * fitScale * zoom;
    var maxX = Math.max(0, (width - stage.clientWidth) / 2);
    var maxY = Math.max(0, (height - stage.clientHeight) / 2);
    offsetX = Math.max(-maxX, Math.min(maxX, offsetX));
    offsetY = Math.max(-maxY, Math.min(maxY, offsetY));
  }

  function renderFlat() {
    if (!image || projection !== 'flat') return;
    var width = stage.clientWidth;
    var height = stage.clientHeight;
    if (!width || !height) return;
    fitScale = Math.min(width / image.naturalWidth, height / image.naturalHeight);
    clampFlatPan();
    fallback.style.position = 'absolute';
    fallback.style.left = '50%';
    fallback.style.top = '50%';
    fallback.style.right = 'auto';
    fallback.style.bottom = 'auto';
    fallback.style.width = image.naturalWidth + 'px';
    fallback.style.height = image.naturalHeight + 'px';
    fallback.style.maxWidth = 'none';
    fallback.style.maxHeight = 'none';
    fallback.style.objectFit = 'fill';
    fallback.style.transform = 'translate(-50%, -50%) translate(' + offsetX + 'px, ' + offsetY + 'px) scale(' + (fitScale * zoom) + ')';
    fallback.style.transformOrigin = 'center center';
  }

  function setRoom(index) {
    if (!rooms.length) return;
    roomIndex = Math.max(0, Math.min(rooms.length - 1, index));
    var room = rooms[roomIndex];
    projection = room.projection === 'flat' ? 'flat' : 'equirectangular';
    title.textContent = room.name + (projection === 'flat' ? ' · پانورامای موبایل' : ' · ۳۶۰ درجه');
    Array.prototype.forEach.call(roomsNav.children, function (button, i) {
      button.setAttribute('aria-current', i === roomIndex ? 'true' : 'false');
    });
    yaw = 0; pitch = 0; fov = 1.15; zoom = 1.25; offsetX = 0; offsetY = 0;
    if (raf) window.cancelAnimationFrame(raf);
    raf = 0;
    canvas.hidden = projection !== 'equirectangular';
    fallback.hidden = projection === 'equirectangular' && !!gl;
    image = new Image();
    image.onload = function () {
      if (projection === 'equirectangular' && gl) {
        gl.bindTexture(gl.TEXTURE_2D, texture);
        gl.pixelStorei(gl.UNPACK_FLIP_Y_WEBGL, true);
        gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGBA, gl.RGBA, gl.UNSIGNED_BYTE, image);
        draw();
      } else {
        fallback.src = room.url;
        fallback.hidden = false;
        if (projection === 'flat') renderFlat();
      }
    };
    image.src = room.url;
  }

  function openTour() {
    var source = document.querySelector('[data-tour-data]');
    if (!source) return;
    try { rooms = JSON.parse(source.textContent || '[]'); } catch (e) { rooms = []; }
    if (!rooms.length) return;
    roomsNav.innerHTML = '';
    rooms.forEach(function (room, index) {
      var button = document.createElement('button');
      button.type = 'button';
      button.textContent = room.name;
      button.addEventListener('click', function () { setRoom(index); });
      roomsNav.appendChild(button);
    });
    if (typeof dialog.showModal === 'function') dialog.showModal();
    document.documentElement.classList.add('tour-open');
    setRoom(0);
  }

  function closeTour() {
    if (dialog.open) dialog.close();
    document.documentElement.classList.remove('tour-open');
    if (raf) window.cancelAnimationFrame(raf);
    raf = 0;
    pointers = {};
  }

  function activeSurface() {
    return projection === 'flat' ? fallback : canvas;
  }

  function pointerDown(event) {
    if (event.pointerType === 'mouse' && event.button !== 0) return;
    var surface = activeSurface();
    if (surface.setPointerCapture) surface.setPointerCapture(event.pointerId);
    pointers[event.pointerId] = { x: event.clientX, y: event.clientY };
    if (Object.keys(pointers).length === 1) {
      dragging = true; lastX = event.clientX; lastY = event.clientY;
    } else if (Object.keys(pointers).length === 2) {
      var points = Object.keys(pointers).map(function (key) { return pointers[key]; });
      pinchStart = Math.hypot(points[1].x - points[0].x, points[1].y - points[0].y);
      pinchZoom = projection === 'flat' ? zoom : fov;
      dragging = false;
    }
    event.preventDefault();
  }

  function pointerMove(event) {
    if (!pointers[event.pointerId]) return;
    var previous = pointers[event.pointerId];
    pointers[event.pointerId] = { x: event.clientX, y: event.clientY };
    var keys = Object.keys(pointers);
    if (keys.length >= 2 && pinchStart > 0) {
      var points = keys.slice(0, 2).map(function (key) { return pointers[key]; });
      var distance = Math.hypot(points[1].x - points[0].x, points[1].y - points[0].y);
      var ratio = distance / pinchStart;
      if (projection === 'flat') {
        zoom = Math.max(1, Math.min(6, pinchZoom * ratio));
        renderFlat();
      } else {
        fov = Math.max(0.55, Math.min(1.65, pinchZoom / ratio));
      }
      return;
    }
    if (!dragging) return;
    var dx = event.clientX - previous.x;
    var dy = event.clientY - previous.y;
    if (projection === 'flat') {
      offsetX += dx; offsetY += dy; renderFlat();
    } else {
      yaw -= dx * 0.004;
      pitch = Math.max(-1.35, Math.min(1.35, pitch + dy * 0.004));
    }
    lastX = event.clientX; lastY = event.clientY;
  }

  function pointerUp(event) {
    delete pointers[event.pointerId];
    dragging = Object.keys(pointers).length === 1;
    if (!dragging) pinchStart = 0;
    else {
      var remaining = pointers[Object.keys(pointers)[0]];
      lastX = remaining.x; lastY = remaining.y;
    }
  }

  function bindSurface(surface) {
    if (!surface) return;
    surface.addEventListener('pointerdown', pointerDown);
    surface.addEventListener('pointermove', pointerMove);
    ['pointerup', 'pointercancel', 'lostpointercapture'].forEach(function (name) {
      surface.addEventListener(name, pointerUp);
    });
    surface.addEventListener('wheel', function (event) {
      event.preventDefault();
      if (projection === 'flat') {
        zoom = Math.max(1, Math.min(6, zoom * (event.deltaY > 0 ? 0.9 : 1.1)));
        renderFlat();
      } else {
        fov = Math.max(0.55, Math.min(1.65, fov + event.deltaY * 0.001));
      }
    }, { passive: false });
  }

  document.querySelectorAll('[data-tour-open]').forEach(function (button) { button.addEventListener('click', openTour); });
  if (closeButton) closeButton.addEventListener('click', closeTour);
  dialog.addEventListener('close', closeTour);
  dialog.addEventListener('click', function (event) { if (event.target === dialog) closeTour(); });

  if (gl) {
    try { setup(); } catch (e) { gl = null; }
  }
  bindSurface(canvas);
  bindSurface(fallback);

  document.addEventListener('keydown', function (event) {
    if (!dialog.open) return;
    if (event.key === 'Escape') closeTour();
    if (event.key === 'ArrowLeft') setRoom(roomIndex + 1);
    if (event.key === 'ArrowRight') setRoom(roomIndex - 1);
  });
  window.addEventListener('resize', function () {
    if (projection === 'flat') renderFlat();
    else resize();
  });
})();
