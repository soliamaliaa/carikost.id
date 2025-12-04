<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$my_id = $_SESSION['user_id'];
$my_role = $_SESSION['role'];
$lawan_id = $_GET['lawan_id'] ?? '';

if (!$lawan_id) {
    die("Error: Tidak tahu mau chat dengan siapa.");
}

// Ambil data lawan bicara untuk header
try {
    $lawanData = $database->getReference('users/' . $lawan_id)->getValue();
} catch (Exception $e) {
    $lawanData = [];
}
$namaLawan = $lawanData['nama_lengkap'] ?? 'Pengguna';

// Buat Room ID Unik (Gabungan ID terurut agar konsisten siapa pun yang buka)
// Contoh: chat_userA_userB
$ids = [$my_id, $lawan_id];
sort($ids);
$room_id = "room_" . $ids[0] . "_" . $ids[1];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat dengan <?= htmlspecialchars($namaLawan) ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- TEMA WARNA (TEAL / TOSCA) --- */
        :root {
            --primary-gradient: linear-gradient(135deg, #00695c 0%, #4db6ac 100%);
            --main-color: #00796B; 
            --chat-bg: #e5ddd5; /* Warna background klasik */
            --msg-me-bg: #e0f2f1; /* Teal sangat muda */
            --msg-other-bg: #ffffff;
        }

        body { 
            font-family: 'Poppins', sans-serif; 
            background-color: #f0f2f5; 
            margin: 0; 
            display: flex; 
            flex-direction: column; 
            height: 100vh; 
        }
        
        /* HEADER */
        .chat-header { 
            background: var(--primary-gradient); 
            color: white; 
            padding: 15px 20px; 
            display: flex; 
            align-items: center; 
            gap: 15px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
            z-index: 10;
        }
        
        .back-btn { 
            color: white; 
            text-decoration: none; 
            font-size: 1.2em; 
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: 0.3s;
        }
        .back-btn:hover { background: rgba(255,255,255,0.2); }

        .avatar-small {
            width: 40px;
            height: 40px;
            background: white;
            color: var(--main-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.1em;
        }

        .user-info h3 { margin: 0; font-size: 1.1em; font-weight: 600; }
        .user-info p { margin: 0; font-size: 0.8em; opacity: 0.9; font-weight: 400; }

        /* AREA CHAT */
        .chat-container { 
            flex: 1; 
            padding: 20px; 
            overflow-y: auto; 
            display: flex; 
            flex-direction: column; 
            gap: 8px; 
            background-image: url("https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png"); /* Background Pattern WhatsApp-like opsional */
            background-color: #e5ddd5;
            background-blend-mode: soft-light;
        }
        
        .message { 
            max-width: 75%; 
            padding: 10px 15px; 
            border-radius: 12px; 
            position: relative; 
            word-wrap: break-word; 
            font-size: 0.95em; 
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            line-height: 1.4;
        }
        
        .msg-me { 
            align-self: flex-end; 
            background: var(--msg-me-bg); 
            color: #333;
            border-top-right-radius: 0; 
        }
        
        .msg-other { 
            align-self: flex-start; 
            background: var(--msg-other-bg); 
            border-top-left-radius: 0; 
        }
        
        .time { 
            font-size: 0.7em; 
            color: #999; 
            display: block; 
            text-align: right; 
            margin-top: 4px; 
        }

        /* INPUT AREA */
        .input-area { 
            background: white; 
            padding: 10px 15px; 
            display: flex; 
            gap: 10px; 
            align-items: center; 
            border-top: 1px solid #ddd;
        }
        
        .input-area input { 
            flex: 1; 
            padding: 12px 20px; 
            border-radius: 25px; 
            border: 1px solid #ddd; 
            outline: none; 
            font-family: 'Poppins', sans-serif;
            background: #f9f9f9;
            transition: 0.3s;
        }
        .input-area input:focus { background: white; border-color: var(--main-color); }

        .btn-send { 
            background: var(--main-color); 
            color: white; 
            border: none; 
            width: 45px; 
            height: 45px; 
            border-radius: 50%; 
            cursor: pointer; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            transition: 0.2s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .btn-send:hover { transform: scale(1.05); background: #004D40; }
        .btn-send i { margin-left: -2px; margin-top: 1px; }

        /* Date Separator */
        .date-divider {
            text-align: center;
            margin: 15px 0;
            position: relative;
        }
        .date-divider span {
            background: #dbe4ea;
            color: #555;
            padding: 5px 12px;
            border-radius: 10px;
            font-size: 0.75em;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

    <div class="chat-header">
        <a href="chat_list.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i></a>
        <div class="avatar-small">
            <?= strtoupper(substr($namaLawan, 0, 1)) ?>
        </div>
        <div class="user-info">
            <h3><?= htmlspecialchars($namaLawan) ?></h3>
            <p><?= ($my_role == 'penyewa') ? 'Pemilik Kost' : 'Penyewa' ?></p>
        </div>
    </div>

    <div class="chat-container" id="chat-box">
        <div class="date-divider"><span>Mulai Percakapan</span></div>
    </div>

    <form class="input-area" id="chat-form">
        <input type="text" id="msg-input" placeholder="Ketik pesan..." autocomplete="off" required>
        <button type="submit" class="btn-send"><i class="fa-solid fa-paper-plane"></i></button>
    </form>

    <script type="module">
        import { initializeApp } from "https://www.gstatic.com/firebasejs/9.6.1/firebase-app.js";
        import { getDatabase, ref, push, onChildAdded, set, serverTimestamp } from "https://www.gstatic.com/firebasejs/9.6.1/firebase-database.js";

        // --- PASTE CONFIG FIREBASE ANDA DI SINI (WAJIB) ---
        const firebaseConfig = {
            apiKey: "AIzaSyBPTpTMza5rDBHGEXvEXvaWW0kHaQ6RzQk",
            authDomain: "carikost-id-5a759.firebaseapp.com",
            databaseURL: "https://carikost-id-5a759-default-rtdb.asia-southeast1.firebasedatabase.app",
            projectId: "carikost-id-5a759",
            storageBucket: "carikost-id-5a759.firebasestorage.app",
            messagingSenderId: "276729027154",
            appId: "1:276729027154:web:0dc7d6bb054c32f894dd8b",
            measurementId: "G-JVMME7YQX3"
        };

        const app = initializeApp(firebaseConfig);
        const db = getDatabase(app);

        const roomId = "<?= $room_id ?>";
        const myId = "<?= $my_id ?>";
        const lawanId = "<?= $lawan_id ?>";
        const myName = "<?= $_SESSION['nama'] ?>";
        const lawanName = "<?= $namaLawan ?>";

        const messagesRef = ref(db, 'chats/' + roomId + '/messages');

        // 1. KIRIM PESAN
        document.getElementById('chat-form').addEventListener('submit', (e) => {
            e.preventDefault();
            const input = document.getElementById('msg-input');
            const text = input.value;

            if (text.trim() === "") return;

            // Simpan pesan
            push(messagesRef, {
                sender: myId,
                text: text,
                timestamp: serverTimestamp()
            });

            // Update Daftar Chat (Agar muncul di list 'Pesan Masuk' kedua user)
            // User Saya -> Lawan
            set(ref(db, 'user_chats/' + myId + '/' + roomId), {
                lawan_id: lawanId,
                lawan_nama: lawanName,
                last_msg: text,
                timestamp: serverTimestamp()
            });
            // User Lawan -> Saya
            set(ref(db, 'user_chats/' + lawanId + '/' + roomId), {
                lawan_id: myId,
                lawan_nama: myName,
                last_msg: text,
                timestamp: serverTimestamp()
            });

            input.value = ""; 
            input.focus();
        });

        // 2. TERIMA PESAN (REALTIME LISTENER)
        const chatBox = document.getElementById('chat-box');
        
        onChildAdded(messagesRef, (data) => {
            const msg = data.val();
            const isMe = (msg.sender === myId);
            
            // Format Waktu (Jam:Menit)
            const date = new Date(msg.timestamp);
            const timeStr = date.getHours().toString().padStart(2, '0') + ":" + date.getMinutes().toString().padStart(2, '0');

            const div = document.createElement('div');
            div.className = "message " + (isMe ? "msg-me" : "msg-other");
            div.innerHTML = `
                ${msg.text}
                <span class="time">${timeStr}</span>
            `;
            
            chatBox.appendChild(div);
            // Auto scroll ke bawah dengan smooth
            chatBox.scrollTo({ top: chatBox.scrollHeight, behavior: 'smooth' });
        });
    </script>

</body>
</html>