# Medical Student Image Generator

Generate animated images of medical students for your landing page using Google's Gemini AI.

## Setup

1. Install dependencies:
```bash
cd medical-student-images
npm install
```

2. The API key is already configured in the `.env` file.

## Usage

Generate all medical student images:
```bash
npm run generate
```

Or run with:
```bash
npm run dev
```

## Generated Images

The script will create 6 different animated scenes:

1. **Studying Anatomy** - Student with anatomical models and 3D visualizations
2. **Group Discussion** - Collaborative case study with medical imaging
3. **Clinical Simulation** - Hands-on practice with medical mannequins
4. **Late Night Study** - Cozy late-night study session
5. **Virtual Learning** - Using AR/VR technology for immersive education
6. **Lab Research** - Conducting research in a modern laboratory

All images will be saved in the `generated-images/` directory.

## Customization

To modify the prompts or add new scenarios, edit the `medicalStudentPrompts` array in `src/generateImages.js`. Each prompt includes:

- Subject description
- Background/environment
- Color palette
- Style specifications
- Size (1024x1024)

## Image Details

- **Style**: Modern animation with clean, professional aesthetic
- **Themes**: Medical education, technology, collaboration, and hands-on learning
- **Colors**: Medical whites, blues, and warm accent lighting
- **Format**: PNG (with transparency support)
- **Resolution**: 1024x1024 pixels

These images are perfect for landing pages, educational websites, or medical training platforms.