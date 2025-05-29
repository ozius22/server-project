from flask import Flask, request, jsonify
import face_recognition
from PIL import Image
from io import BytesIO

app = Flask(__name__)
reference_faces = []

@app.route('/load_references', methods=['POST'])
def load_references():
    global reference_faces
    reference_faces = []

    for image_file in request.files.getlist("images"):
        image = face_recognition.load_image_file(image_file)
        encodings = face_recognition.face_encodings(image)
        if encodings:
            reference_faces.append(encodings[0])
    return jsonify({"status": "loaded", "count": len(reference_faces)})

@app.route('/match', methods=['POST'])
def match():
    if not reference_faces:
        return jsonify({"error": "No references loaded"}), 400

    test_image = face_recognition.load_image_file(request.files['image'])
    test_encodings = face_recognition.face_encodings(test_image)
    matches = []

    for encoding in test_encodings:
        results = face_recognition.compare_faces(reference_faces, encoding, tolerance=0.5)
        matches.append(any(results))

    return jsonify({"any_match": any(matches)})

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5005)
