(function () {
  'use strict';

  var dialog = document.querySelector('[data-property-tour]');
  if (!dialog) return;

  var canvas = dialog.querySelector('canvas');
  var title = dialog.querySelector('[data-tour-title]');
  var roomsNav = dialog.querySelector('[data-tour-rooms]');
  var closeButton = dialog.querySelector('[data-tour-close]');
  var gl = canvas && (canvas.getContext('webgl', { alpha: false, antialias: true }) || canvas.getContext('experimental-webgl'));
  var program, texture, image, yaw = 0, pitch = 0, fov = 1.15, dragging = false, lastX = 0, lastY = 0, roomIndex = 0, rooms = [];
  var raf = 0;

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
    if (!gl) return;
    var ratio = Math.min(window.devicePixelRatio || 1, 2);
    var w = Math.max(1, Math.floor(canvas.clientWidth * ratio));
    var h = Math.max(1, Math.floor(canvas.clientHeight * ratio));
    if (canvas.width !== w || canvas.height !== h) { canvas.width = w; canvas.height = h; }
    gl.viewport(0, 0, w, h);
  }

  function draw() {
    if (!gl || !image) return;
    resize();
    gl.useProgram(program);
    gl.uniform1f(gl.getUniformLocation(program, 'yaw'), yaw);
    gl.uniform1f(gl.getUniformLocation(program, 'pitch'), pitch);
    gl.uniform1f(gl.getUniformLocation(program, 'fov'), fov);
    gl.uniform1f(gl.getUniformLocation(program, 'aspect'), canvas.width / canvas.height);
    gl.drawArrays(gl.TRIANGLE_STRIP, 0, 4);
    raf = window.requestAnimationFrame(draw);
  }

  function setRoom(index) {
    if (!rooms.length) return;
    roomIndex = Math.max(0, Math.min(rooms.length - 1, index));
    var room = rooms[roomIndex];
    title.textContent = room.name;
    Array.prototype.forEach.call(roomsNav.children, function (button, i) {
      button.setAttribute('aria-current', i === roomIndex ? 'true' : 'false');
    });
    yaw = 0; pitch = 0;
    image = new Image();
    image.onload = function () {
      if (gl) {
        gl.bindTexture(gl.TEXTURE_2D, texture);
        gl.pixelStorei(gl.UNPACK_FLIP_Y_WEBGL, true);
        gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGBA, gl.RGBA, gl.UNSIGNED_BYTE, image);
        draw();
      } else {
        var fallback = dialog.querySelector('[data-tour-fallback]');
        if (fallback) { fallback.src = room.url; fallback.hidden = false; }
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
  }

  document.querySelectorAll('[data-tour-open]').forEach(function (button) { button.addEventListener('click', openTour); });
  if (closeButton) closeButton.addEventListener('click', closeTour);
  dialog.addEventListener('close', closeTour);
  dialog.addEventListener('click', function (event) { if (event.target === dialog) closeTour(); });

  if (gl) {
    try { setup(); } catch (e) { gl = null; }
  }
  if (canvas) {
    canvas.addEventListener('pointerdown', function (event) {
      dragging = true; lastX = event.clientX; lastY = event.clientY;
      if (canvas.setPointerCapture) canvas.setPointerCapture(event.pointerId);
    });
    canvas.addEventListener('pointermove', function (event) {
      if (!dragging) return;
      var dx = event.clientX - lastX, dy = event.clientY - lastY;
      lastX = event.clientX; lastY = event.clientY;
      yaw -= dx * 0.004; pitch = Math.max(-1.35, Math.min(1.35, pitch + dy * 0.004));
    });
    ['pointerup', 'pointercancel', 'lostpointercapture'].forEach(function (name) {
      canvas.addEventListener(name, function () { dragging = false; });
    });
    canvas.addEventListener('wheel', function (event) {
      event.preventDefault(); fov = Math.max(0.55, Math.min(1.65, fov + event.deltaY * 0.001));
    }, { passive: false });
  }
  document.addEventListener('keydown', function (event) {
    if (!dialog.open) return;
    if (event.key === 'Escape') closeTour();
    if (event.key === 'ArrowLeft') setRoom(roomIndex + 1);
    if (event.key === 'ArrowRight') setRoom(roomIndex - 1);
  });
  window.addEventListener('resize', resize);
})();
