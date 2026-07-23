<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$level = $data['level'];
$progress = $data['progress'];
$score = $data['score'];
$user_id = $_SESSION['user_id']; // Your authentication system

$conn = new mysqli('localhost', 'username', 'password', 'database');

$sql = "INSERT INTO game_progress (user_id, level_number, score, progress_percentage, completed) 
        VALUES (?, ?, ?, ?, ?) 
        ON DUPLICATE KEY UPDATE 
        score = GREATEST(score, VALUES(score)),
        progress_percentage = GREATEST(progress_percentage, VALUES(progress_percentage)),
        completed = IF(VALUES(progress_percentage) >= 70, TRUE, completed)";

$stmt = $conn->prepare($sql);
$completed = $progress >= 70;
$stmt->bind_param("iiidi", $user_id, $level, $score, $progress, $completed);
$stmt->execute();

echo json_encode(['success' => true]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Level 1: Packet Voyager - Internet Governance Game</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #0a0a2e 0%, #1a1a4e 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            font-family: 'Courier New', monospace;
            color: #00ff00;
        }
        
        .game-container {
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid #00ff00;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 0 30px rgba(0, 255, 0, 0.3);
        }
        
        .game-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 10px;
            background: rgba(0, 255, 0, 0.1);
            border-radius: 10px;
        }
        
        .stats {
            display: flex;
            gap: 30px;
        }
        
        .stat {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .stat-label {
            font-size: 12px;
            color: #00cc00;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #00ff00;
        }
        
        canvas {
            border: 1px solid #00ff00;
            background: #000011;
            display: block;
            margin: 0 auto;
        }
        
        .instructions {
            margin-top: 15px;
            text-align: center;
            color: #00cc00;
            font-size: 14px;
        }
        
        .level-info {
            text-align: center;
            margin-bottom: 15px;
            color: #ffaa00;
            font-size: 16px;
            font-weight: bold;
        }
        
        .progress-bar {
            width: 100%;
            height: 20px;
            background: rgba(0, 255, 0, 0.1);
            border: 1px solid #00ff00;
            border-radius: 10px;
            margin-top: 15px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #00ff00, #00cc00);
            width: 0%;
            transition: width 0.3s;
        }
        
        button {
            background: #00ff00;
            color: #000;
            border: none;
            padding: 10px 20px;
            font-family: 'Courier New', monospace;
            font-weight: bold;
            cursor: pointer;
            border-radius: 5px;
            margin: 5px;
        }
        
        button:hover {
            background: #00cc00;
            box-shadow: 0 0 15px rgba(0, 255, 0, 0.5);
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .modal-content {
            background: #0a0a2e;
            border: 2px solid #00ff00;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            max-width: 500px;
        }
    </style>
</head>
<body>
    <div class="game-container">
        <div class="level-info">🌐 LEVEL 1: PACKET VOYAGER</div>
        <div class="game-header">
            <div class="stats">
                <div class="stat">
                    <span class="stat-label">📦 Packets</span>
                    <span class="stat-value" id="packetsDelivered">0</span>
                </div>
                <div class="stat">
                    <span class="stat-label">⭐ Score</span>
                    <span class="stat-value" id="score">0</span>
                </div>
                <div class="stat">
                    <span class="stat-label">❤️ Lives</span>
                    <span class="stat-value" id="lives">3</span>
                </div>
            </div>
            <button onclick="startGame()">Start Game</button>
        </div>
        <canvas id="gameCanvas" width="800" height="500"></canvas>
        <div class="progress-bar">
            <div class="progress-fill" id="progressFill"></div>
        </div>
        <div class="instructions">
            🎮 Arrow Keys to move | Avoid RED threats | Collect GREEN checkpoints | Reach YELLOW destination
        </div>
    </div>

    <div class="modal" id="quizModal">
        <div class="modal-content">
            <h2>📝 Knowledge Check</h2>
            <p id="quizQuestion"></p>
            <div id="quizOptions"></div>
            <p id="quizFeedback" style="margin-top: 15px;"></p>
        </div>
    </div>

    <script>
        const canvas = document.getElementById('gameCanvas');
        const ctx = canvas.getContext('2d');
        
        // Game state
        let gameRunning = false;
        let player = {
            x: 50,
            y: 250,
            radius: 12,
            speed: 5
        };
        
        let checkpoints = [];
        let threats = [];
        let destination = { x: 750, y: 250 };
        let packetsDelivered = 0;
        let score = 0;
        let lives = 3;
        let levelProgress = 0;
        const TARGET_SCORE = 100; // Score needed to pass level
        
        // Threat types representing internet governance issues
        const threatTypes = [
            { name: 'Malware', color: '#ff0000', speed: 2, points: -10 },
            { name: 'Data Tracker', color: '#ff6600', speed: 1.5, points: -15 },
            { name: 'Censorship', color: '#ff00ff', speed: 2.5, points: -20 },
            { name: 'Throttling', color: '#ffff00', speed: 3, points: -25 }
        ];
        
        // Checkpoint types representing internet infrastructure
        const checkpointTypes = [
            { name: 'Router', color: '#00ffff', points: 10 },
            { name: 'DNS Server', color: '#00ff88', points: 15 },
            { name: 'IXP', color: '#88ff00', points: 20 },
            { name: 'Fiber Node', color: '#ffffff', points: 25 }
        ];
        
        // Game objects
        function createThreat() {
            const type = threatTypes[Math.floor(Math.random() * threatTypes.length)];
            return {
                x: Math.random() * canvas.width,
                y: Math.random() * canvas.height,
                radius: 15,
                vx: (Math.random() - 0.5) * type.speed * 2,
                vy: (Math.random() - 0.5) * type.speed * 2,
                type: type
            };
        }
        
        function createCheckpoint() {
            const type = checkpointTypes[Math.floor(Math.random() * checkpointTypes.length)];
            return {
                x: Math.random() * (canvas.width - 100) + 50,
                y: Math.random() * (canvas.height - 100) + 50,
                radius: 10,
                collected: false,
                type: type
            };
        }
        
        function initGame() {
            threats = [];
            checkpoints = [];
            
            // Create threats (representing internet dangers)
            for (let i = 0; i < 5; i++) {
                threats.push(createThreat());
            }
            
            // Create checkpoints
            for (let i = 0; i < 3; i++) {
                checkpoints.push(createCheckpoint());
            }
            
            player.x = 50;
            player.y = 250;
            packetsDelivered = 0;
            score = 0;
            lives = 3;
            levelProgress = 0;
            
            updateStats();
        }
        
        function startGame() {
            initGame();
            gameRunning = true;
            gameLoop();
        }
        
        function updateStats() {
            document.getElementById('packetsDelivered').textContent = packetsDelivered;
            document.getElementById('score').textContent = score;
            document.getElementById('lives').textContent = lives;
            
            const progress = Math.min((score / TARGET_SCORE) * 100, 100);
            document.getElementById('progressFill').style.width = progress + '%';
            levelProgress = progress;
        }
        
        // Collision detection
        function checkCollision(obj1, obj2) {
            const dx = obj1.x - obj2.x;
            const dy = obj1.y - obj2.y;
            const distance = Math.sqrt(dx * dx + dy * dy);
            return distance < obj1.radius + obj2.radius;
        }
        
        function handleCollisions() {
            // Check threat collisions
            threats.forEach(threat => {
                if (checkCollision(player, threat)) {
                    score += threat.type.points;
                    lives--;
                    // Reset player position
                    player.x = 50;
                    player.y = 250;
                    
                    if (lives <= 0) {
                        gameOver();
                    }
                }
            });
            
            // Check checkpoint collisions
            checkpoints.forEach(checkpoint => {
                if (!checkpoint.collected && checkCollision(player, checkpoint)) {
                    checkpoint.collected = true;
                    score += checkpoint.type.points;
                    showLearningPopup(checkpoint.type);
                }
            });
            
            // Check destination reached
            if (checkCollision(player, destination)) {
                packetsDelivered++;
                score += 30;
                
                // Reset checkpoints and add new threats
                checkpoints.forEach(cp => cp.collected = false);
                threats.push(createThreat());
                threats.push(createThreat());
                
                // Move destination
                destination.x = Math.random() * (canvas.width - 100) + 50;
                destination.y = Math.random() * (canvas.height - 100) + 50;
                
                // Check if level complete
                if (score >= TARGET_SCORE) {
                    levelComplete();
                }
            }
            
            updateStats();
        }
        
        function showLearningPopup(checkpointType) {
            const messages = {
                'Router': '🌐 Routers direct internet traffic - they\'re like post offices for data!',
                'DNS Server': '📖 DNS translates website names to IP addresses - the internet\'s phonebook!',
                'IXP': '🔗 Internet Exchange Points connect different networks - crucial for net neutrality!',
                'Fiber Node': '💡 Fiber optic cables carry data as light - the backbone of internet speed!'
            };
            
            // Show floating text
            ctx.fillStyle = '#00ff00';
            ctx.font = '14px Courier New';
            ctx.fillText(messages[checkpointType.name], player.x, player.y - 30);
        }
        
        function levelComplete() {
            gameRunning = false;
            showQuiz();
        }
        
        function gameOver() {
            gameRunning = false;
            alert('Game Over! Try again to protect internet freedom!');
            initGame();
        }
        
        function showQuiz() {
            const questions = [
                {
                    question: "What is Net Neutrality?",
                    options: [
                        "All internet traffic should be treated equally",
                        "Internet should be fast for everyone",
                        "Networks should be neutral colored",
                        "Only governments should control internet"
                    ],
                    correct: 0
                },
                {
                    question: "What does DNS stand for?",
                    options: [
                        "Digital Network System",
                        "Domain Name System",
                        "Data Navigation Service",
                        "Dynamic Network Security"
                    ],
                    correct: 1
                }
            ];
            
            const question = questions[Math.floor(Math.random() * questions.length)];
            document.getElementById('quizQuestion').textContent = question.question;
            
            const optionsHtml = question.options.map((opt, index) => 
                `<button onclick="checkAnswer(${index}, ${question.correct})" style="display: block; width: 100%; margin: 5px 0;">
                    ${opt}
                </button>`
            ).join('');
            
            document.getElementById('quizOptions').innerHTML = optionsHtml;
            document.getElementById('quizModal').style.display = 'flex';
        }
        
        function checkAnswer(userAnswer, correctAnswer) {
            const feedback = document.getElementById('quizFeedback');
            if (userAnswer === correctAnswer) {
                feedback.innerHTML = '✅ Correct! Internet governance knowledge gained!';
                feedback.style.color = '#00ff00';
                // Here you would update the database with level completion
                updateDatabase(1, levelProgress);
            } else {
                feedback.innerHTML = '❌ Incorrect. Review internet governance concepts and try again.';
                feedback.style.color = '#ff0000';
            }
            
            setTimeout(() => {
                document.getElementById('quizModal').style.display = 'none';
                // In full implementation, this would unlock next level
                alert('Level Complete! Next level unlocked: Privacy Fortress');
            }, 2000);
        }
        
        function updateDatabase(level, progress) {
            // PHP/MariaDB integration point
            fetch('update_progress.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    level: level,
                    progress: progress,
                    score: score
                })
            });
        }
        
        // Movement
        const keys = {};
        document.addEventListener('keydown', (e) => {
            keys[e.key] = true;
        });
        document.addEventListener('keyup', (e) => {
            keys[e.key] = false;
        });
        
        function movePlayer() {
            if (keys['ArrowUp'] || keys['w']) player.y -= player.speed;
            if (keys['ArrowDown'] || keys['s']) player.y += player.speed;
            if (keys['ArrowLeft'] || keys['a']) player.x -= player.speed;
            if (keys['ArrowRight'] || keys['d']) player.x += player.speed;
            
            // Boundaries
            player.x = Math.max(player.radius, Math.min(canvas.width - player.radius, player.x));
            player.y = Math.max(player.radius, Math.min(canvas.height - player.radius, player.y));
        }
        
        // Drawing functions
        function drawPlayer() {
            // Player (data packet)
            ctx.beginPath();
            ctx.arc(player.x, player.y, player.radius, 0, Math.PI * 2);
            ctx.fillStyle = '#00ff00';
            ctx.fill();
            ctx.strokeStyle = '#ffffff';
            ctx.stroke();
            
            // Packet data lines
            ctx.strokeStyle = '#00ff00';
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.moveTo(player.x - 5, player.y - 5);
            ctx.lineTo(player.x + 5, player.y + 5);
            ctx.moveTo(player.x + 5, player.y - 5);
            ctx.lineTo(player.x - 5, player.y + 5);
            ctx.stroke();
        }
        
        function drawThreats() {
            threats.forEach(threat => {
                ctx.beginPath();
                ctx.arc(threat.x, threat.y, threat.radius, 0, Math.PI * 2);
                ctx.fillStyle = threat.type.color;
                ctx.fill();
                ctx.strokeStyle = '#ffffff';
                ctx.stroke();
                
                // Threat label
                ctx.fillStyle = '#ffffff';
                ctx.font = '10px Arial';
                ctx.textAlign = 'center';
                ctx.fillText(threat.type.name, threat.x, threat.y - 20);
            });
        }
        
        function drawCheckpoints() {
            checkpoints.forEach(checkpoint => {
                if (!checkpoint.collected) {
                    ctx.beginPath();
                    ctx.arc(checkpoint.x, checkpoint.y, checkpoint.radius, 0, Math.PI * 2);
                    ctx.fillStyle = checkpoint.type.color;
                    ctx.fill();
                    ctx.strokeStyle = '#ffffff';
                    ctx.stroke();
                    
                    ctx.fillStyle = '#000000';
                    ctx.font = '10px Arial';
                    ctx.textAlign = 'center';
                    ctx.fillText(checkpoint.type.name, checkpoint.x, checkpoint.y + 3);
                }
            });
        }
        
        function drawDestination() {
            ctx.beginPath();
            ctx.arc(destination.x, destination.y, 15, 0, Math.PI * 2);
            ctx.fillStyle = '#ffff00';
            ctx.fill();
            ctx.strokeStyle = '#ffaa00';
            ctx.lineWidth = 3;
            ctx.stroke();
            
            // Pulse effect
            const pulse = Math.sin(Date.now() / 500) * 5;
            ctx.beginPath();
            ctx.arc(destination.x, destination.y, 20 + pulse, 0, Math.PI * 2);
            ctx.strokeStyle = '#ffff00';
            ctx.lineWidth = 1;
            ctx.stroke();
        }
        
        function updateThreats() {
            threats.forEach(threat => {
                threat.x += threat.vx;
                threat.y += threat.vy;
                
                // Bounce off walls
                if (threat.x < threat.radius || threat.x > canvas.width - threat.radius) {
                    threat.vx *= -1;
                }
                if (threat.y < threat.radius || threat.y > canvas.height - threat.radius) {
                    threat.vy *= -1;
                }
                
                // Keep in bounds
                threat.x = Math.max(threat.radius, Math.min(canvas.width - threat.radius, threat.x));
                threat.y = Math.max(threat.radius, Math.min(canvas.height - threat.radius, threat.y));
            });
        }
        
        function gameLoop() {
            if (!gameRunning) return;
            
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            // Draw grid (representing internet infrastructure)
            ctx.strokeStyle = 'rgba(0, 255, 0, 0.1)';
            ctx.lineWidth = 0.5;
            for (let i = 0; i < canvas.width; i += 50) {
                ctx.beginPath();
                ctx.moveTo(i, 0);
                ctx.lineTo(i, canvas.height);
                ctx.stroke();
            }
            for (let i = 0; i < canvas.height; i += 50) {
                ctx.beginPath();
                ctx.moveTo(0, i);
                ctx.lineTo(canvas.width, i);
                ctx.stroke();
            }
            
            movePlayer();
            updateThreats();
            handleCollisions();
            
            drawCheckpoints();
            drawDestination();
            drawThreats();
            drawPlayer();
            
            requestAnimationFrame(gameLoop);
        }
        
        // Initial setup
        initGame();
        
        // Close modal on escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.getElementById('quizModal').style.display = 'none';
            }
        });

        // Add this to your main game file
function checkLevelUnlock(currentLevel) {
    const TARGET_PERCENTAGE = 70; // 70% to unlock next level
    
    // Check if user met the target
    if (levelProgress >= TARGET_PERCENTAGE) {
        // Unlock next level
        fetch('unlock_level.php', {
            method: 'POST',
            body: JSON.stringify({
                level: currentLevel + 1
            })
        }).then(() => {
            showNotification('🔓 Next Level Unlocked!');
        });
    }
}
    </script>
</body>
</html>