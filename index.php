<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Detector de gestos</title>
    <style>
        body {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f0f0f0;
        }
        h1 {
            color: #333;
        }
        #main-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            margin-top: 20px;
        }
        #webcam-container {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 200px;
            height: auto;
        }
        #resultado {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 300px;
        }
        img {
            max-width: 100%;
            width: 300px;
            height: auto;
            border-radius: 10px;
        }
        button {
            padding: 10px 20px;
            margin: 10px;
            font-size: 16px;
            cursor: pointer;
            border: none;
            background: #333;
            color: white;
            border-radius: 5px;
        }
        button:hover {
            background: #555;
        }
    </style>
</head>
<body>
    <h1>Detector de gestos</h1>
    <div>Muéstrame un gesto, un like, un dislike, algo...</div>
    <button type="button" id="start-button" onclick="init()">Comenzar</button>
    <button type="button" id="stop-button" onclick="stop()" disabled>Terminar</button>
    <div id="main-container">
        <div id="webcam-container"></div>
        <div id="resultado"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@latest/dist/tf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@teachablemachine/image@latest/dist/teachablemachine-image.min.js"></script>
    
    <script type="text/javascript">
        const URL = "./modelo/";

        let model, webcam, maxPredictions;
        let isPredicting = false;

        // Cargar el modelo y configurar la webcam
        async function init() {
            const modelURL = URL + "model.json";
            const metadataURL = URL + "metadata.json";

            model = await tmImage.load(modelURL, metadataURL);
            maxPredictions = model.getTotalClasses();

            // Detener cualquier webcam previa
            if (webcam) {
                stop();
            }

            // Configurar la cámara web más pequeña
            const flip = true;
            webcam = new tmImage.Webcam(200, 200, flip); // Ancho y alto ajustados
            await webcam.setup();
            await webcam.play();
            window.requestAnimationFrame(loop);

            // Limpiar contenedor antes de agregar nueva cámara
            document.getElementById("webcam-container").innerHTML = "";
            document.getElementById("webcam-container").appendChild(webcam.canvas);

            // Actualizar los botones
            document.getElementById("start-button").disabled = true;
            document.getElementById("stop-button").disabled = false;
            isPredicting = true;
        }

        // Función de bucle para realizar predicciones mientras la cámara está encendida
        async function loop() {
            if (isPredicting) {
                webcam.update();
                await predict();
                window.requestAnimationFrame(loop);
            }
        }

        // Detener la cámara y las predicciones
        function stop() {
            if (webcam) {
                webcam.stop();
                webcam = null;
            }
            isPredicting = false;

            // Limpiar contenedor de webcam y resultado
            document.getElementById("webcam-container").innerHTML = "";
            document.getElementById("resultado").innerHTML = "";

            // Actualizar botones
            document.getElementById("start-button").disabled = false;
            document.getElementById("stop-button").disabled = true;
        }

        // Realizar predicciones con el modelo
        async function predict() {
            const prediction = await model.predict(webcam.canvas);

            let like = 0;
            let dislike = 0;
            let enojo = 0;

            for (let i = 0; i < maxPredictions; i++) {
                const className = prediction[i].className;
                const probability = prediction[i].probability.toFixed(2);

                if (className === "like") {
                    like = parseFloat(probability);
                }
                if (className === "dislike") {
                    dislike = parseFloat(probability);
                }
                if (className === "enojo") {
                    enojo = parseFloat(probability);
                }
            }

            // Determinar cuál es la predicción dominante con umbral de 0.9
            updateGestureImage(like, dislike, enojo);
        }

        // Función para actualizar la imagen basada en las probabilidades
        function updateGestureImage(like, dislike, enojo) {
            const umbral = 0.95;
            const resultadoDiv = document.getElementById("resultado");
            resultadoDiv.innerHTML = ""; // Limpiar antes de mostrar la nueva imagen

            const imgElement = document.createElement("img");
            
            if (like > dislike && like > enojo && like >= umbral) {
                imgElement.src = "img/like.png";
            } else if (dislike > like && dislike > enojo && dislike >= umbral) {
                imgElement.src = "img/dislike.png";
            } else if (enojo > like && enojo > dislike && enojo >= umbral) {
                imgElement.src = "img/angry.png";
            } else {
                imgElement.src = "img/question.png";
            }

            resultadoDiv.appendChild(imgElement);
        }
    </script>
</body>
</html>
