class FarastPcmProcessor extends AudioWorkletProcessor {
  constructor() {
    super();
    this.buffer = [];
    this.targetSamples = 1600;
  }

  process(inputs) {
    const input = inputs[0]?.[0];
    if (!input) return true;

    for (let i = 0; i < input.length; i++) this.buffer.push(input[i]);

    while (this.buffer.length >= this.targetSamples) {
      const samples = this.buffer.splice(0, this.targetSamples);
      const pcm = new Int16Array(samples.length);
      for (let i = 0; i < samples.length; i++) {
        const sample = Math.max(-1, Math.min(1, samples[i]));
        pcm[i] = sample < 0 ? sample * 32768 : sample * 32767;
      }
      this.port.postMessage(pcm.buffer, [pcm.buffer]);
    }

    return true;
  }
}

registerProcessor('farast-pcm-processor', FarastPcmProcessor);
