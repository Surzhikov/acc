from flask import Flask, request, jsonify
from stressrnn import StressRNN

app = Flask(__name__)

@app.route('/stress', methods=['POST'])
def stress():
    text = request.json['text']
    stress_rnn = StressRNN()
    stressed_text = stress_rnn.put_stress(text, stress_symbol='+', accuracy_threshold=0.75, replace_similar_symbols=True)

    return jsonify({'stressed_text': stressed_text})

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)
