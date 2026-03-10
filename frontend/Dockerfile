FROM node:20-alpine

WORKDIR /app

# Copy package files
COPY package*.json ./

# Install dependencies
RUN npm install

# Copy the rest of the source code
COPY . .

# Build frontend
RUN npm run build

# Stage de serve per mantenir el contenidor viu (opcional si usem volums, però útil per debug)
# En aquest cas, simplement farem que el contenidor es quedi esperant
CMD ["sh", "-c", "echo 'Frontend build complete. Files are in /app/dist' && tail -f /dev/null"]
