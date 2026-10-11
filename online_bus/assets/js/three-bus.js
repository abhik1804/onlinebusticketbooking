/**
 * Shreenathji Travels - Interactive 3D Luxury Bus Experience
 * Powered by Three.js
 */

document.addEventListener('DOMContentLoaded', function () {
  const container = document.getElementById('three-bus-canvas-wrap');
  if (!container || typeof THREE === 'undefined') {
    return;
  }

  let scene, camera, renderer;
  let busGroup, roadMesh, wheels = [];
  let roadLines = [];
  let clouds = [];
  let mouseX = 0, mouseY = 0;
  let windowHalfX = container.clientWidth / 2;
  let windowHalfY = container.clientHeight / 2;

  init();
  animate();

  function init() {
    // 1. Scene & Camera
    scene = new THREE.Scene();
    scene.fog = new THREE.FogExp2(0x0f172a, 0.015);

    const width = container.clientWidth;
    const height = container.clientHeight;

    camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 1000);
    camera.position.set(16, 8, 20);
    camera.lookAt(0, 2, 0);

    // 2. Renderer
    renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setSize(width, height);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    container.appendChild(renderer.domElement);

    // 3. Lighting
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.85);
    scene.add(ambientLight);

    const sunLight = new THREE.DirectionalLight(0xfef08a, 1.2);
    sunLight.position.set(25, 40, 20);
    sunLight.castShadow = true;
    sunLight.shadow.mapSize.width = 1024;
    sunLight.shadow.mapSize.height = 1024;
    scene.add(sunLight);

    const blueRimLight = new THREE.DirectionalLight(0x38bdf8, 0.6);
    blueRimLight.position.set(-20, 20, -20);
    scene.add(blueRimLight);

    // 4. Create Road & Markings
    createRoad();

    // 5. Create 3D Luxury Bus
    createLuxuryBus();

    // 6. Create Sky Clouds
    createClouds();

    // 7. Event Listeners
    window.addEventListener('resize', onWindowResize);
    container.addEventListener('mousemove', onMouseMove);
  }

  function createRoad() {
    // Asphalt Road plane
    const roadGeo = new THREE.PlaneGeometry(160, 16);
    const roadMat = new THREE.MeshStandardMaterial({
      color: 0x1e293b,
      roughness: 0.85,
      metalness: 0.1
    });
    roadMesh = new THREE.Mesh(roadGeo, roadMat);
    roadMesh.rotation.x = -Math.PI / 2;
    roadMesh.position.y = 0;
    roadMesh.receiveShadow = true;
    scene.add(roadMesh);

    // Road Dashed Center Markings
    const lineGeo = new THREE.PlaneGeometry(3.5, 0.35);
    const lineMat = new THREE.MeshBasicMaterial({ color: 0xfbbf24 });

    for (let i = -70; i <= 70; i += 8) {
      const lineMesh = new THREE.Mesh(lineGeo, lineMat);
      lineMesh.rotation.x = -Math.PI / 2;
      lineMesh.position.set(i, 0.02, 0);
      scene.add(lineMesh);
      roadLines.push(lineMesh);
    }
  }

  function createLuxuryBus() {
    busGroup = new THREE.Group();

    // Main Coach Body (Aerodynamic Volvo Multi-Axle shape)
    const bodyGeo = new THREE.BoxGeometry(11, 3.2, 3.4);
    const bodyMat = new THREE.MeshStandardMaterial({
      color: 0x1d4ed8, // Royal Blue
      metalness: 0.3,
      roughness: 0.25
    });
    const busBody = new THREE.Mesh(bodyGeo, bodyMat);
    busBody.position.y = 2.4;
    busBody.castShadow = true;
    busBody.receiveShadow = true;
    busGroup.add(busBody);

    // Gold Luxury Accent Stripe
    const stripeGeo = new THREE.BoxGeometry(11.05, 0.45, 3.45);
    const stripeMat = new THREE.MeshStandardMaterial({
      color: 0xf59e0b,
      metalness: 0.7,
      roughness: 0.2
    });
    const stripe = new THREE.Mesh(stripeGeo, stripeMat);
    stripe.position.set(0, 1.8, 0);
    busGroup.add(stripe);

    // Front Windshield (Aerodynamic Slanted Cockpit)
    const glassMat = new THREE.MeshStandardMaterial({
      color: 0x0284c7,
      transparent: true,
      opacity: 0.85,
      roughness: 0.1,
      metalness: 0.8
    });
    const frontWindshieldGeo = new THREE.BoxGeometry(0.3, 1.8, 3.2);
    const frontWindshield = new THREE.Mesh(frontWindshieldGeo, glassMat);
    frontWindshield.position.set(5.4, 2.7, 0);
    busGroup.add(frontWindshield);

    // Side Passenger Windows Strip
    const windowStripGeo = new THREE.BoxGeometry(9.6, 1.2, 3.46);
    const windowStrip = new THREE.Mesh(windowStripGeo, glassMat);
    windowStrip.position.set(-0.4, 2.9, 0);
    busGroup.add(windowStrip);

    // Headlights (Glowing Yellow/White LEDs)
    const lightMat = new THREE.MeshBasicMaterial({ color: 0xfffbeb });
    const headlightGeo = new THREE.CylinderGeometry(0.2, 0.2, 0.2, 16);
    headlightGeo.rotateZ(Math.PI / 2);

    const leftLight = new THREE.Mesh(headlightGeo, lightMat);
    leftLight.position.set(5.5, 1.4, 1.1);
    busGroup.add(leftLight);

    const rightLight = new THREE.Mesh(headlightGeo, lightMat);
    rightLight.position.set(5.5, 1.4, -1.1);
    busGroup.add(rightLight);

    // Headlight Beams (Spotlights shining forward on the road)
    const spotL = new THREE.SpotLight(0xfef08a, 2.5, 30, Math.PI / 6, 0.4, 1);
    spotL.position.set(5.6, 1.4, 1.1);
    spotL.target.position.set(25, 0, 1.1);
    busGroup.add(spotL);
    busGroup.add(spotL.target);

    const spotR = new THREE.SpotLight(0xfef08a, 2.5, 30, Math.PI / 6, 0.4, 1);
    spotR.position.set(5.6, 1.4, -1.1);
    spotR.target.position.set(25, 0, -1.1);
    busGroup.add(spotR);
    busGroup.add(spotR.target);

    // Tail Lights (Red glow)
    const tailMat = new THREE.MeshBasicMaterial({ color: 0xef4444 });
    const tailGeo = new THREE.BoxGeometry(0.1, 0.4, 0.4);
    const tailL = new THREE.Mesh(tailGeo, tailMat);
    tailL.position.set(-5.52, 1.6, 1.2);
    busGroup.add(tailL);

    const tailR = new THREE.Mesh(tailGeo, tailMat);
    tailR.position.set(-5.52, 1.6, -1.2);
    busGroup.add(tailR);

    // AC Rooftop Unit
    const acGeo = new THREE.BoxGeometry(3.5, 0.5, 2.2);
    const acMat = new THREE.MeshStandardMaterial({ color: 0xffffff, roughness: 0.5 });
    const acUnit = new THREE.Mesh(acGeo, acMat);
    acUnit.position.set(-0.5, 4.2, 0);
    busGroup.add(acUnit);

    // Wheels (4 Multi-Axle wheels: Front, Rear 1, Rear 2)
    const wheelPositions = [
      [3.5, 0.8, 1.65],
      [3.5, 0.8, -1.65],
      [-2.8, 0.8, 1.65],
      [-2.8, 0.8, -1.65],
      [-4.3, 0.8, 1.65],
      [-4.3, 0.8, -1.65]
    ];

    const tireGeo = new THREE.CylinderGeometry(0.8, 0.8, 0.45, 24);
    tireGeo.rotateX(Math.PI / 2);
    const rimGeo = new THREE.CylinderGeometry(0.45, 0.45, 0.47, 16);
    rimGeo.rotateX(Math.PI / 2);

    const tireMat = new THREE.MeshStandardMaterial({ color: 0x0f172a, roughness: 0.9 });
    const rimMat = new THREE.MeshStandardMaterial({ color: 0xe2e8f0, metalness: 0.8, roughness: 0.2 });

    wheelPositions.forEach(pos => {
      const wheelGroup = new THREE.Group();
      const tire = new THREE.Mesh(tireGeo, tireMat);
      tire.castShadow = true;
      wheelGroup.add(tire);

      const rim = new THREE.Mesh(rimGeo, rimMat);
      wheelGroup.add(rim);

      wheelGroup.position.set(pos[0], pos[1], pos[2]);
      busGroup.add(wheelGroup);
      wheels.push(wheelGroup);
    });

    scene.add(busGroup);
  }

  function createClouds() {
    const cloudGeo = new THREE.DodecahedronGeometry(3, 1);
    const cloudMat = new THREE.MeshLambertMaterial({
      color: 0x64748b,
      transparent: true,
      opacity: 0.35
    });

    for (let i = 0; i < 6; i++) {
      const cloud = new THREE.Mesh(cloudGeo, cloudMat);
      cloud.position.set(-50 + i * 25, 14 + (i % 3) * 2, -15 - (i % 2) * 8);
      scene.add(cloud);
      clouds.push(cloud);
    }
  }

  function onMouseMove(event) {
    const rect = container.getBoundingClientRect();
    mouseX = (event.clientX - rect.left - windowHalfX) * 0.02;
    mouseY = (event.clientY - rect.top - windowHalfY) * 0.02;
  }

  function onWindowResize() {
    if (!container) return;
    const width = container.clientWidth;
    const height = container.clientHeight;
    windowHalfX = width / 2;
    windowHalfY = height / 2;
    camera.aspect = width / height;
    camera.updateProjectionMatrix();
    renderer.setSize(width, height);
  }

  let clock = new THREE.Clock();

  function animate() {
    requestAnimationFrame(animate);

    const delta = clock.getDelta();
    const time = clock.getElapsedTime();

    // 1. Move Road Lines (Speed simulation)
    const speed = 28 * delta;
    roadLines.forEach(line => {
      line.position.x -= speed;
      if (line.position.x < -70) {
        line.position.x += 140;
      }
    });

    // 2. Rotate Wheels
    wheels.forEach(wheel => {
      wheel.rotation.z -= speed * 1.5;
    });

    // 3. Gentle Bus Suspension Bounce
    if (busGroup) {
      busGroup.position.y = Math.sin(time * 6) * 0.04;
      busGroup.rotation.z = Math.sin(time * 4) * 0.005;
    }

    // 4. Drift Clouds
    clouds.forEach(cloud => {
      cloud.position.x -= 2 * delta;
      if (cloud.position.x < -60) {
        cloud.position.x = 60;
      }
    });

    // 5. Interactive Mouse Camera Tilt
    camera.position.x += (16 + mouseX - camera.position.x) * 0.05;
    camera.position.y += (8 - mouseY - camera.position.y) * 0.05;
    camera.lookAt(0, 2, 0);

    renderer.render(scene, camera);
  }
});
