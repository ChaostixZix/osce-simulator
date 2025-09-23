import { GoogleGenAI } from '@google/genai';
import mime from 'mime';
import { writeFile, mkdir } from 'fs/promises';
import { join } from 'path';
import { config } from 'dotenv';

// Load environment variables
config();

const ai = new GoogleGenAI({
  apiKey: process.env.GEMINI_API_KEY,
});

const OUTPUT_DIR = process.env.OUTPUT_DIR || './generated-images';

// Medical student image prompts
const medicalStudentPrompts = [
  {
    name: 'studying_anatomy',
    prompt: `Create an animated scene of a diverse medical student studying human anatomy. The student is sitting at a modern desk with anatomical models, textbooks, and a tablet showing 3D anatomy visualizations. Warm, focused lighting with a clean study environment. The student has a thoughtful expression, wearing a white lab coat over casual clothes. Style: modern animation, clean lines, professional medical education theme. Colors: soft blues, whites, and warm accent lighting. Size: 1024x1024`
  },
  {
    name: 'group_discussion',
    prompt: `Animated scene of three diverse medical students engaged in a collaborative case study discussion. They're gathered around a digital table displaying medical imaging scans and patient data. Dynamic pose showing active conversation and teamwork. Modern hospital or medical school library background. Style: contemporary animation with clean, professional aesthetic. Colors: medical whites, blues, with energetic accents. Size: 1024x1024`
  },
  {
    name: 'clinical_simulation',
    prompt: `Animated medical student practicing with a high-fidelity medical mannequin in a simulation lab. The student is wearing scrubs and focused on performing a clinical procedure. Modern simulation room with medical equipment monitors visible. Professional lighting with a focus on the hands-on learning experience. Style: realistic animation with attention to medical detail. Colors: clinical greens and whites with technology blue accents. Size: 1024x1024`
  },
  {
    name: 'late_night_study',
    prompt: `Animated scene of a medical student studying late at night in a cozy dorm room. Surrounded by medical textbooks, notes, and a laptop showing educational content. Warm lamplight creating a focused, intimate atmosphere. A coffee cup sits nearby. The student looks determined but not overwhelmed. Style: warm, inviting animation with soft lighting. Colors: warm amber lighting, deep blues for night time, comfortable study space feel. Size: 1024x1024`
  },
  {
    name: 'virtual_learning',
    prompt: `Modern medical student using VR/AR technology for immersive learning. The student is wearing AR glasses while interacting with holographic medical visualizations. Futuristic but realistic setting showing the intersection of technology and medical education. Clean, minimalist environment with focus on the digital interface. Style: futuristic animation with clean lines and tech-inspired design. Colors: sleek whites, blues, and neon accents for digital elements. Size: 1024x1024`
  },
  {
    name: 'lab_research',
    prompt: `Animated medical student conducting research in a modern laboratory setting. Working with microscopes, petri dishes, and digital analysis tools. Wearing proper lab attire with a focused, curious expression. Bright, clean laboratory environment with equipment in the background. Style: scientific animation with attention to detail. Colors: clean whites, laboratory stainless steel, and subtle scientific blue lighting. Size: 1024x1024`
  }
];

async function ensureOutputDir() {
  try {
    await mkdir(OUTPUT_DIR, { recursive: true });
  } catch (error) {
    if (error.code !== 'EEXIST') {
      throw error;
    }
  }
}

async function saveBinaryFile(fileName, content) {
  const filePath = join(OUTPUT_DIR, fileName);
  await writeFile(filePath, content, 'binary');
  console.log(`✅ File saved: ${filePath}`);
}

async function generateImage(prompt, fileName) {
  console.log(`🎨 Generating image: ${fileName}`);
  
  const config = {
    responseModalities: ['IMAGE', 'TEXT'],
  };
  
  const model = 'gemini-2.5-flash-image-preview';
  const contents = [
    {
      role: 'user',
      parts: [
        { text: prompt },
      ],
    },
  ];

  try {
    const response = await ai.models.generateContentStream({
      model,
      config,
      contents,
    });

    let fileIndex = 0;
    for await (const chunk of response) {
      if (!chunk.candidates || !chunk.candidates[0].content || !chunk.candidates[0].content.parts) {
        continue;
      }
      
      if (chunk.candidates[0].content.parts[0]?.inlineData) {
        const inlineData = chunk.candidates[0].content.parts[0].inlineData;
        const fileExtension = mime.getExtension(inlineData.mimeType || 'image/png');
        const outputFileName = `${fileName}_${fileIndex}.${fileExtension}`;
        const buffer = Buffer.from(inlineData.data || '', 'base64');
        await saveBinaryFile(outputFileName, buffer);
        fileIndex++;
      } else if (chunk.text) {
        console.log(`📝 Text response: ${chunk.text}`);
      }
    }
  } catch (error) {
    console.error(`❌ Error generating image for ${fileName}:`, error.message);
  }
}

async function generateAllImages() {
  console.log('🚀 Starting medical student image generation...');
  
  await ensureOutputDir();
  
  for (const { name, prompt } of medicalStudentPrompts) {
    console.log(`\n📚 Processing: ${name}`);
    await generateImage(prompt, `medical_student_${name}`);
    // Add a small delay between requests to avoid rate limiting
    await new Promise(resolve => setTimeout(resolve, 2000));
  }
  
  console.log('\n✅ All images generated successfully!');
  console.log(`📁 Check the ${OUTPUT_DIR} directory for the generated images.`);
}

// Main execution
if (import.meta.url === `file://${process.argv[1]}`) {
  generateAllImages().catch(console.error);
}

export { generateAllImages, medicalStudentPrompts };