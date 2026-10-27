
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Home | Building Approval Management System</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f3f5f9;
            color: #263445;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        header {
            background: #1d3b60;
            color: white;
            padding: 24px 35px;
        }

        header h1 {
            font-size: 28px;
            margin-bottom: 8px;
        }

        header p {
            font-size: 15px;
            color: #e0e9f5;
        }

        main {
            width: 100%;
            max-width: 1000px;
            margin: 35px auto;
            padding: 0 22px;
            flex: 1;
        }

        .welcome-card {
            background: white;
            padding: 32px;
            border-radius: 14px;
            box-shadow: 0 5px 18px #0000000c;
        }

        .welcome-card h2 {
            color: #17365d;
            font-size: 24px;
            margin-bottom: 16px;
        }

        .welcome-card p {
            color: #53657b;
            font-size: 15px;
            line-height: 1.8;
            margin-bottom: 22px;
        }

        .dashboard-button {
            display: inline-block;
            background: #1d3b60;
            color: white;
            text-decoration: none;
            padding: 13px 22px;
            border-radius: 7px;
            font-size: 14px;
            font-weight: bold;
            transition: background 0.2s, transform 0.2s;
        }

        .dashboard-button:hover {
            background: #315b91;
            transform: translateY(-2px);
        }

        .features {
            margin-top: 28px;
            padding-top: 22px;
            border-top: 1px solid #e5eaf1;
        }

        .features h3 {
            color: #17365d;
            font-size: 17px;
            margin-bottom: 12px;
        }

        .features p {
            margin-bottom: 0;
            font-size: 14px;
        }

        footer {
            text-align: center;
            padding: 25px 15px;
            color: #718096;
            font-size: 14px;
        }

        @media (max-width: 600px) {
            header {
                padding: 23px 20px;
            }

            header h1 {
                font-size: 23px;
            }

            main {
                margin: 24px auto;
                padding: 0 15px;
            }

            .welcome-card {
                padding: 24px 20px;
            }
        }
    </style>
</head>

<body>

    <header>
        <h1>Building Approval Management System</h1>
        <p>Digital platform for building application and approval management</p>
    </header>

    <main>
        <section class="welcome-card">
            <h2>Welcome!</h2>

            <p>
                Manage citizen details, property records, building
                applications, site inspections, and approval status
                through one centralized platform.
            </p>

            <a href="dashboard.php" class="dashboard-button">
                Go to Dashboard &rarr;
            </a>

            <div class="features">
                <h3>About the System</h3>
                <p>
                    This system provides a centralized interface for
                    managing citizen and property information, tracking
                    building applications, recording site inspections,
                    maintaining approval records, and managing engineers.
                </p>
            </div>
        </section>
    </main>

    <footer>
        Building Approval Management System | RDBMS Project
    </footer>

</body>
</html>
